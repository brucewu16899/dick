<?php namespace App\Http\Controllers\Admin;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Dick\CRUD\Http\Controllers\CrudController;
use Illuminate\Http\Request;

// VALIDATION: change the requests to match your own file names if you need form validation
use App\Http\Requests\PageRequest as StoreRequest;
use App\Http\Requests\PageRequest as UpdateRequest;

class PageCrudController extends CrudController {

    public $crud = array(
                        // what's the namespace for your entity's model
                        "model" => "App\Models\Page",
                        // what name will show up on the buttons, in singural (ex: Add entity)
                        "entity_name" => "page",
                        // what name will show up on the buttons, in plural (ex: Delete 5 entities)
                        "entity_name_plural" => "pages",
                        // what route have you defined for your entity? used for links.
                        "route" => "admin/page",

                        // *****
                        // COLUMNS
                        // *****
                        //
                        // Define the columns for the table view as an array:
                        //
                        "columns" => [
                                            [
                                                'name' => 'name',
                                                'label' => "Page name"
                                            ],
                                    ],

                        // *****
                        // FIELDS
                        // *****
                        //
                        // Define the fields for the "Edit item" and "Add item" views as an array:
                        //
                        "fields" => [
                                                [
                                                    'name' => 'template',
                                                    'label' => "Template",
                                                    'type' => 'select_template',
                                                    'options' => [], // populated automatically in the use_template method
                                                    'allows_null' => false
                                                ],
                                    ],

                        );


    // Overwrites the CrudController create() method to add template usage.
    public function create($template = false)
    {
        $this->use_template($template);

        return parent::create();
    }


    // Overwrites the CrudController store() method to add template usage.
    public function store(StoreRequest $request)
    {
        $this->use_template(\Request::input('template'));
        return parent::store_crud();
    }


    // Overwrites the CrudController edit() method to add template usage.
    public function edit($id, $template = false)
    {
        // use the template in the GET parameter if it exists
        if ($template) {
            $this->use_template($template);
        }
        // otherwise use the template value stored in the database
        else
        {
            $model = $this->crud['model'];
            $this->data['entry'] = $model::findOrFail($id);
            $this->use_template($this->data['entry']->template);
        }

        return parent::edit($id);
    }


    // Overwrites the CrudController update() method to add template usage.
    public function update(UpdateRequest $request)
    {
        $this->use_template(\Request::input('template'));
        return parent::update_crud();
    }


    // -----------------------------------------------
    // Methods that are particular to the PageManager.
    // -----------------------------------------------


    private function get_templates()
    {
        // get the files from config/dick/page_templates
        $template_files = \Storage::disk('config')->files('dick/page_templates');

        if (!count($template_files))
        {
            abort('403', 'Template files are missing.');
        }

        $templates = [];

        foreach ($template_files as $k => $template_file) {
            // get the file name
            $file_name = str_replace('.php', '', last(explode('/', $template_file)));
            // get the pretty template name
            $templates[$file_name] = config('dick.page_templates.'.$file_name.'.template_name');
        }

        return $templates;
    }

    private function use_template($file_name = false) {
        if (!$file_name) {
            $file_name = array_keys($this->get_templates())[0];
        }

        // merge the fields defined above and the ones set in the template file
        $this->crud['fields'] = array_merge($this->crud['fields'], config('dick.page_templates.'.$file_name.'.fields'));

        // set the possible options for the "templates" field and select the default value
        foreach ($this->crud['fields'] as $key => $field) {
            if ($field['name'] == 'template') {
                $this->crud['fields'][$key]['value'] = $file_name;
                $this->crud['fields'][$key]['options'] = $this->get_templates();
            }
        }
    }
}
