<?php

namespace Ignite\Crud;

use Closure;
use Ignite\Config\ConfigHandler;
use Ignite\Crud\Fields\Component;
use Ignite\Crud\Models\Form;
use Ignite\Exceptions\Traceable\InvalidArgumentException;
use Ignite\Page\Actions\ActionModal;
use Ignite\Page\Page;
use Ignite\Support\Facades\Config;
use Ignite\Support\Vue\ButtonComponent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Traits\ForwardsCalls;
use Illuminate\Support\Traits\Macroable;

class BaseCrudShow extends Page
{
    use ForwardsCalls,
        Macroable {
            __call as macroCall;
        }

    /**
     * Is registering component in card.
     *
     * @var bool
     */
    protected $inCard = false;

    /**
     * Page root Vue Component.
     *
     * @var string
     */
    protected $rootComponent = 'lit-crud-form-page';

    /**
     * Form instance.
     *
     * @var BaseForm
     */
    protected $form;

    /**
     * Appended attributes.
     *
     * @var array
     */
    protected $appends = [];

    /**
     * Query resolver.
     *
     * @var Closure
     */
    protected $queryResolver;

    /**
     * Event handlers for the associated events.
     *
     * @var array
     */
    protected $events = [];

    /**
     * ConfigHandler instance.
     *
     * @var ConfigHandler
     */
    protected $config;

    /**
     * Create new CrudShow instance.
     *
     * @param  ConfigHandler $config
     * @param  BaseForm      $form
     * @return void
     */
    public function __construct(ConfigHandler $config, BaseForm $form)
    {
        parent::__construct();

        $this->config = $config;
        $this->form = $form;

        // Add form lifecycle hooks.
        $this->form->registering(fn ($field) => $this->registeringField($field));
        $this->form->registered(fn ($field) => $this->registeredField($field));
    }

    /**
     * Add crud event handler.
     *
     * @param  string  $event
     * @param  Closure $closure
     * @return void
     */
    public function on($event, Closure $closure)
    {
        if (! array_key_exists($event, $this->events)) {
            $this->events[$event] = [];
        }

        $this->events[$event][] = $closure;
    }

    /**
     * Fire model event.
     *
     * @param  string $event
     * @param  array  ...$parameters
     * @return void
     */
    public function fireEvent($event, ...$parameters)
    {
        if (! array_key_exists($event, $this->events)) {
            return;
        }

        foreach ($this->events[$event] as $closure) {
            $closure(...Arr::flatten($parameters));
        }
    }

    /**
     * Set attributes that should be append.
     *
     * @param  array ...$appends
     * @return $this
     */
    public function appends(...$appends)
    {
        $this->appends = Arr::flatten($appends);

        return $this;
    }

    /**
     * Get appends.
     *
     * @return array
     */
    public function getAppends()
    {
        return $this->appends;
    }

    /**
     * Add chart.
     *
     * @param  string                $name
     * @return \Ignite\Vue\Component
     */
    public function chart(string $name)
    {
        $chart = parent::chart($name);

        $chart->setAttribute('send_model_id',
            ! is_subclass_of($this->form->getModel(), Form::class)
            && $this->form->getModel() != Form::class
        );

        return $chart;
    }

    /**
     * Set query resolver.
     *
     * @param  Closure $closure
     * @return $this
     */
    public function query(Closure $closure)
    {
        $this->queryResolver = $closure;

        return $this;
    }

    /**
     * Resolve query.
     *
     * @param  Builder $query
     * @return void
     */
    public function resolveQuery($query)
    {
        if (! $this->queryResolver instanceof Closure) {
            return;
        }

        call_user_func($this->queryResolver, $query);
    }

    /**
     * Add subpage.
     *
     * @param  string          $config
     * @return ButtonComponent
     */
    public function subPage($config, $icon = null)
    {
        $config = Config::get($config);

        $title = $config->names['plural'];
        $prefix = $config->routePrefix();

        if ($this->isOneRelation($query = $config->controllerInstance()->getQuery())) {
            if (! $model = $query->first()) {
                return;
            }

            $config->setModelInstance($model);
            $title = $config->names()['singular'];
            $prefix = "{$prefix}/{$model->id}";
        }

        return $this->headerLeft()->component(new ButtonComponent)
            ->size('sm')
            ->variant('transparent')
            ->prop('href', lit()->url($prefix))
            ->domProp('innerHTML', $icon ? "{$icon} {$title}" : $title);
    }

    /**
     * Determines if query is instance of a "one relation".
     *
     * @param  Relation $query
     * @return bool
     */
    protected function isOneRelation($query)
    {
        $relations = [
            BelongsTo::class, HasOne::class, MorphOne::class, MorphTo::class,
            HasOneThrough::class,
        ];

        foreach ($relations as $relation) {
            if ($query instanceof $relation) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add crud preview for the given url.
     *
     * @param  string          $url
     * @return ButtonComponent
     */
    public function preview($url)
    {
        if ($url instanceof Closure) {
            $url = $url(app()->getLocale());
        }

        $preview = component('lit-crud-preview')->prop('url', $url);

        return $this->headerRight()
            ->component(new ButtonComponent)
            ->child($preview)
            ->size('sm')
            ->variant('primary');
    }

    /**
     * Add action to page.
     *
     * @param  string          $title
     * @param  string          $action
     * @return Component|mixed
     */
    public function action($title, $action)
    {
        $actionInstance = app()->make($action);

        $button = (new ButtonComponent())
            ->child($title)
            ->size('sm')
            ->variant('primary')
            ->class('mb-3');

        $component = component('lit-action')
            ->prop('wrapper', $button)
            ->on('run', RunActionEvent::class)
            ->prop('eventData', ['action' => $action]);

        if (method_exists($actionInstance, 'modal')) {
            $component->prop('modal', $modal = new ActionModal);

            $actionInstance->modal(
                    $modal->title($title)
                );
        }

        $this->resolveAction($component);

        $this->wrapper('lit-col', function () use ($component) {
            $this->component($component);
        });

        return $button;
    }

    /**
     * Resolve action component.
     *
     * @param  \Ignite\Vue\Component $component
     * @return void
     */
    public function resolveAction($component)
    {
        $component->on('run', RunCrudActionEvent::class)
            ->prop('eventData', array_merge(
                $component->getProp('eventData'),
                [
                    'config' => $this->config->getNamespace(),
                    'model'  => $this->form->getModel(),
                ]
            ));
    }

    /**
     * Registering field lifecycle hook.
     *
     * @param  Field $field
     * @return void
     */
    protected function registeringField($field)
    {
        if (! $this->inCard()) {
            throw new InvalidArgumentException('Fields must be registered inside a card.', [
                'function' => '__call',
            ]);
        }
    }

    /**
     * Registered Field lifecycle hook.
     *
     * @param  Field $field
     * @return void
     */
    protected function registeredField($field)
    {
        return $this->wrapper
            ->component('lit-field')
            ->prop('field', $field);
    }

    /**
     * Add group wrapper.
     *
     * @param  Closure   $closure
     * @return Component
     */
    public function group(Closure $closure)
    {
        return $this->wrapper('lit-field-wrapper-group', function () use ($closure) {
            $closure($this);
        });
    }

    /**
     * Is registering component in card.
     *
     * @return bool
     */
    public function inCard()
    {
        return $this->inCard;
    }

    /**
     * Add Vue component.
     *
     * @param  string                $component
     * @return \Ignite\Vue\Component
     */
    public function component($component)
    {
        if ($this->inWrapper()) {
            $component = component($component);

            $this->wrapper->component($component);

            return $component;
        }

        return parent::component($component);
    }

    /**
     * Create a new Card.
     *
     * @param  any  ...$params
     * @return void
     */
    public function info(string $title = '')
    {
        $info = $this->component('lit-info')->title($title);

        if ($this->inCard()) {
            $info->heading('h6');
        }

        return $info;
    }

    /**
     * Create b-card wrapper.
     *
     * @param  int     $cols
     * @param  Closure $closure
     * @return void
     */
    public function card(Closure $closure)
    {
        return parent::card(function ($form) use ($closure) {
            $this->inCard = true;
            $closure($this);
            $this->inCard = false;
        });
    }

    /**
     * Get attributes.
     *
     * @return array
     */
    public function render(): array
    {
        return array_merge($this->form->render(), parent::render());
    }

    /**
     * Get form instance.
     *
     * @param  Request             $request
     * @return BaseForm|mixed|null
     */
    public function getForm(Request $request)
    {
        return $this->form;
    }

    /**
     * Call CrudShow method.
     *
     * @param  string $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters = [])
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        return $this->forwardCallTo($this->form, $method, $parameters);
    }
}
