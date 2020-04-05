<?php

namespace Fjord\Form\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Route;
use Fjord\TrackEdits\FormEdit;
use Fjord\Form\Database\FormField;
use Fjord\Form\FormFieldCollection;
use Fjord\Support\Facades\FormLoader;

class FormController extends Controller
{
    public function update(Request $request, $id)
    {
        $formField = FormField::findOrFail($id);

        $formField->update($request->all());

        $edit = new FormEdit();
        $edit->fjord_user_id = fjord_user()->id;
        $edit->collection = $formField->collection;
        $edit->form_name = $formField->form_name;
        $edit->created_at = \Carbon\Carbon::now();
        $edit->save();

        $formField->append('last_edit');

        return $formField;
    }

    public function show(Request $request)
    {
        $routeSplit = explode('.', Route::currentRouteName());
        $formName = array_pop($routeSplit);
        $collection = last($routeSplit);

        $this->setForm($collection, $formName);

        $eloquentFormFields = $this->getFormFields($collection, $formName);

        $this->form->setPreviewRoute(
            new FormFieldCollection($eloquentFormFields['data'])
        );

        return view('fjord::app')->withComponent('fj-crud-show')
            ->withModels([
                'model' => $eloquentFormFields
            ])
            ->withTitle($this->form->title)
            ->withProps([
                'formConfig' => $this->form->toArray(),
                'headerComponents' => ['fj-crud-show-preview'],
                'controls' => [],
                'content' => ['fj-crud-show-form']
            ]);
    }

    protected function getFormFields($collection, $form_name)
    {
        $formFields = [];

        foreach ($this->form->form_fields as $key => $field) {

            $formFields[$key] = FormField::firstOrCreate(
                ['collection' => $collection, 'form_name' => $form_name, 'field_id' => $field->id],
                ['content' => $field->default ?? null]
            )->append('last_edit');

            if ($field->type == 'block') {
                $formFields[$key]->withRelation($field->id);
            }

            if ($field->type == 'relation') {
                $formFields[$key]->setFormRelation();
            }
            /*
            if($field['type'] == 'image') {
                $formFields[$key]->withRelation($field['id']);
            }
            */
        }

        return eloquentJs(collect($formFields), FormField::class);
    }

    protected function setForm($collection, $formName)
    {
        $formFieldInstance = new FormField();
        $formFieldInstance->collection = $collection;
        $formFieldInstance->form_name = $formName;

        $this->formPath = $formFieldInstance->form_fields_path;
        $this->form = FormLoader::load($this->formPath, FormField::class);
    }
}
