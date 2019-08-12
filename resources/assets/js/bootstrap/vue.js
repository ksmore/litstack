
import Vue from 'vue'
import Vuex from 'vuex'

import Bus from './../common/event.bus'
import Fjord from './../common/fjord'

import PageShow from './../components/Pages/PageShow';
import CrudShow from './../components/Crud/CrudShow';
import CrudIndex from './../components/Crud/CrudIndex';

// FontAwesome
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library, config } from '@fortawesome/fontawesome-svg-core'
import { far } from '@fortawesome/free-regular-svg-icons'
import { fas } from '@fortawesome/free-solid-svg-icons'
import { fab } from '@fortawesome/free-brands-svg-icons'

import Notifications from 'vue-notification';
import VModal from 'vue-js-modal';
import { ServerTable, ClientTable, Event } from 'vue-tables-2';
import CKEditor from '@ckeditor/ckeditor5-vue';
import BootstrapVue from 'bootstrap-vue';

export default function VueBootstrap() {

    // components
    Fjord.components(require.context('./../components/FormFields/', true, /\.(js|vue)$/i))
    Vue.component('crud-index', CrudIndex);
    Vue.component('crud-show', CrudShow);
    Vue.component('page-show', PageShow);

    // FontAwesome
    library.add(far);
    library.add(fas);
    library.add(fab);
    Vue.component('fa-icon', FontAwesomeIcon)

    // Modules
    Vue.use(BootstrapVue)
    Vue.use(Notifications);
    Vue.use(VModal);
    Vue.use(ClientTable);
    Vue.use(CKEditor);
    Vue.use(BootstrapVue)

    // prototypes
    Vue.prototype.$bus = Bus;
}
