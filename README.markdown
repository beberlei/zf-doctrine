# Doctrine 1 Integration with Zend Framework

This project tries to offer a complete Integration of Doctrine 1 with Zend Framework.
The following components belong to this Integration:

* Zend_Application Resource
* Zend Framework Modular Project Support
* Zend_Tool Provider for Doctrine Model Generation, Migrations and Fixtures
* Zend_Paginator Adapter for Doctrine Queries
* Dynamic Zend_Form generation from Doctrine Models

This integration requires the latest Doctrine version 1.2.2 to work completly

## Installation

### Get it!

#### SVN Export or Externals

Github offers SVN Read support for a while now, you can either use svn export or svn:externals
to include ZFDoctrine into your project or into your PHP Include Path.

    svn checkout http://svn.github.com/beberlei/zf-doctrine.git

#### Git Clone

    git clone git://github.com/beberlei/zf-doctrine.git

### Zend_Tool Configuration

To enable the ZFDoctrine Tool Providers you have to register them in your Zend Tool
configuration. If you have ZFDoctrine in your include path this is as easy as calling:

    zf enable config.provider ZFDoctrine_Tool_DoctrineProvider

If you don't have ZFDoctrine in your include path, you need to configure Zend Tools
include path to do so. Go to your $HOME directory, open up `.zf.ini` and add a line:

    php.include_path = "include-path here"

Now check if the installation worked by calling:

    zf ? doctrine

The result should be help information on all the available commands:

    Zend Framework Command Line Console Tool v1.11.0dev
    Actions supported by provider "Doctrine"
      Doctrine
        zf create-project doctrine dsn zend-project-style[=1] library-per-module single-library
        zf build-project doctrine force load reload
        zf create-database doctrine
        zf drop-database doctrine force
        zf create-tables doctrine
        zf generate-sql doctrine
        zf dql doctrine
        zf load-data doctrine append
        zf dump-data doctrine individual-files
        zf generate-models-from-yaml doctrine
        zf generate-yaml-from-models doctrine
        zf generate-yaml-from-database doctrine
        zf generate-migration doctrine class-name from-database from-models
        zf excecute-migration doctrine to-version
        zf show-migration doctrine


## Setting up a new Zend Framework Project

The Tool support for Doctrine 1 only works if your project contains a .zfproject.xml file that contains
a "BootstrapFile" resource. For a new project you can easily achieve this by calling:

    benny@benny-pc:/tmp$ zf create project my-project
    Creating project at /tmp/my-project
    Note: This command created a web project, for more information setting up your VHOST, please see docs/README

You should now import the ZFDoctrine and Doctrine 1.2 libraries into your
application-root/library folder to make them available in your projects include
path.

To convert an existing project to use ZFDoctrine is a bit more complicated and really not recommended.
If the project does not have a .zfproject.xml you have to create one. If you follow the Zend Standards
you can get away with the following file contents:

    <?xml version="1.0"?>
    <projectProfile type="default" version="1.10">
      <projectDirectory>
        <projectProfileFile filesystemName=".zfproject.xml"/>
        <applicationDirectory classNamePrefix="Application_">
          <configsDirectory>
            <applicationConfigFile type="ini"/>
          </configsDirectory>
          <bootstrapFile filesystemName="Bootstrap.php"/>
        </applicationDirectory>
      </projectDirectory>
    </projectProfile>

Otherwise you have to twiggle on each of the nodes using the "filesystemName" attribute.

## Doctrine and Zend Framework Integration Conventions

You have to follow one convention for ZFDoctrine to allow modular Doctrine models to work:

*Models have to be called <ModuleName>_Model_<Name> for all modules *including*
the module that is considered to be the "default" for the MVC.

Examples:

* Default_Model_User
* Default_Model_Group
* Blog_Model_Post
* Blog_Model_Category

To have Doctrine work with this classes you must explicitly spill out
all the `refClass`, `model`, `local` and `foreign` properties on the
Relations of your Doctrine models.

## Doctrine-Enable a Zend Framework Project

To use tooling support, you have to Doctrine-Enable your Zend Framework Project by calling:

    zf create-project doctrine --dsn=mysql://root:passwd@localhost/my_app --zend-project-style

This enables the Doctrine Zend_Application resource with the given DSN to connecto to a database.
Additionally a project style has to be specified that defines where code-generation puts your
entities.

Additionally there are 4 directories generated inside your application/configs directory:

* `application/configs/schema` - Contains the YAML Schema Metadata
* `application/configs/migrations` - Contains the migration classes
* `application/configs/fixtures` - Contains data-fixtures for your application
* `application/configs/sql` - Contains SQL files

There are three different project styles that make sense in a Zend Framework Project:

* Zend Project Style with `--zend-project-style` is also the default.
* One Library directory per Module `--library-per-module`
* Single Library directory per project `--single-library`

You can change this option at a later stage of the project, however you need to re-generate
all the classes and manually delete the orphaned classes in this case.

You find this option in the application.ini under the section:

    resources.doctrine.manager.attributes.attr_model_loading = "model_loading_zend"

> **Note**
>
> If Doctrine is not in your library or in your Include path you have to add it to the application.ini
>
>     includePaths.library = APPLICATION_PATH "/../library:/home/benny/code/doctrine1/lib"

### 1. Zend Project Style

The default Zend Project style has a folder "models" in each module. For example having
only the default module your directories look like:

    .
    |-- application
    |   |-- Bootstrap.php
    |   |-- configs
    |   |   |-- application.ini
    |   |   |-- fixtures
    |   |   |-- migrations
    |   |   |-- schema
    |   |   `-- sql
    |   |-- controllers
    |   |   |-- ErrorController.php
    |   |   `-- IndexController.php
    |   |-- *models*
    |   `-- views
    |       |-- helpers
    |       `-- scripts
    |           |-- error
    |           |   `-- error.phtml
    |           `-- index
    |               `-- index.phtml
    |-- library
    |-- public
        `-- index.php

Having two additional modules "blog" and "guestbook" we end up with:

    .
    |-- application
    |   |-- Bootstrap.php
    |   |-- configs
    |   |   `-- application.ini
    |   |-- controllers
    |   |   |-- ErrorController.php
    |   |   `-- IndexController.php
    |   |-- *models*
    |   |-- modules
    |   |   |-- blog
    |   |   |   |-- controllers
    |   |   |   |-- *models*
    |   |   |   `-- views
    |   |   |       |-- filters
    |   |   |       |-- helpers
    |   |   |       `-- scripts
    |   |   `-- guestbook
    |   |       |-- controllers
    |   |       |-- *models*
    |   |       `-- views
    |   |           |-- filters
    |   |           |-- helpers
    |   |           `-- scripts
    |   `-- views
    |       |-- helpers
    |       `-- scripts
    |           |-- error
    |           |   `-- error.phtml
    |           `-- index
    |               `-- index.phtml
    |-- library
    |-- public
        `-- index.php

This approach requires you to use the Module Autoloaders, which I personally find slow and unintuitive.

`application.ini` cofig is:

    resources.doctrine.manager.attributes.attr_model_loading = "model_loading_zend"

### 2. Library per Module

In library per module you get some kind of duplication of knowledge in the paths, however you get to keep
the simple Class Underscore to Directory Separator convention.

    .
    |-- application
    |   |-- Bootstrap.php
    |   |-- configs
    |   |   `-- application.ini
    |   |-- controllers
    |   |   |-- ErrorController.php
    |   |   `-- IndexController.php
    |   |-- models
    |   |-- modules
    |   |   |-- blog
    |   |   |   |-- controllers
    |   |   |   |-- library
    |   |   |   |   `-- Blog
    |   |   |   |       `-- Model
    |   |   |   `-- views
    |   |   |       |-- filters
    |   |   |       |-- helpers
    |   |   |       `-- scripts
    |   |   `-- guestbook
    |   |       |-- controllers
    |   |       |-- library
    |   |       |   `-- Guestbook
    |   |       |       `-- Model
    |   |       `-- views
    |   |           |-- filters
    |   |           |-- helpers
    |   |           `-- scripts
    |   `-- views
    |       |-- helpers
    |       `-- scripts
    |           |-- error
    |           |   `-- error.phtml
    |           `-- index
    |               `-- index.phtml
    |-- library
    |-- public
        `-- index.php

Option is:

    resources.doctrine.manager.attributes.attr_model_loading = "model_loading_zend_module_library"

### 3. Single Library Path

I have often seen the approach taken with a single library that contains all the models in subdirectories
of each Modules library directory.

    .
    |-- application
    |   |-- Bootstrap.php
    |   |-- configs
    |   |   `-- application.ini
    |   |-- controllers
    |   |   |-- ErrorController.php
    |   |   `-- IndexController.php
    |   |-- modules
    |   |   |-- blog
    |   |   |   |-- controllers
    |   |   |   `-- views
    |   |   |       |-- filters
    |   |   |       |-- helpers
    |   |   |       `-- scripts
    |   |   `-- guestbook
    |   |       |-- controllers
    |   |       `-- views
    |   |           |-- filters
    |   |           |-- helpers
    |   |           `-- scripts
    |   `-- views
    |       |-- helpers
    |       `-- scripts
    |           |-- error
    |           |   `-- error.phtml
    |           `-- index
    |               `-- index.phtml
    |-- library
    |   |-- Blog
    |   |   `-- Model
    |   |-- Guestbook
    |   |   `-- Model
    |   `-- Model
    |-- public
    |   `-- index.php

Option is:

    resources.doctrine.manager.attributes.attr_model_loading = "model_loading_zend_single_library"

## Generating Models (With a single Module)

Using Zend Doctrine Integration with a Single module first begs the question, what is that modules prefix? By
default, the prefix is "Default_Model_*". You can change this easily by opening up your `application.ini` and
change it to "Zfplanet" for example like in our example, rebuilding Padraic Bradys
[fine application ZFPlanet](http://github.com/padraic/ZFPlanet).

    ; appnamespace is just for automatically adding autoloading
    appnamespace = "Zfplanet"
    ; defaultModule is used for for class generation
    resources.frontController.defaultModule = "zfplanet"

> **Note**
>
> Make sure the defaultModule line is before the controllerDirectory line. Sadly the ordering is important in this one.

Grab the modified `zfplanet.yml` file from the examples/Zfplanet folder of this project and put it in your projects
`application/configs/schema/` folder, then run:

    zf generate-models-from-yaml doctrine

And you should see lots of newly created model files in the `applications/models` folder. Nice huh?

    benny@benny-pc:~/code/php/zfdoctrine/singlemodule$ zf generate-models-from-yaml doctrine
    [Doctrine] Generated record 'Zfplanet_Model_Blog' in Module 'zfplanet'.
    [Doctrine] Generated record 'Zfplanet_Model_Feed' in Module 'zfplanet'.
    [Doctrine] Generated record 'Zfplanet_Model_FeedMeta' in Module 'zfplanet'.
    [Doctrine] Generated record 'Zfplanet_Model_Entry' in Module 'zfplanet'.
    [Doctrine] Generated record 'Zfplanet_Model_Subscription' in Module 'zfplanet'.
    [Doctrine] Generated record 'Zfplanet_Model_User' in Module 'zfplanet'.
    [Doctrine] Successfully generated 6 record classes.

## Generating Table Classes

If you want to generate the Table classes also you have to modify the application.ini to include:

    resources.doctrine.generateModels.generateTableClasses = true

## Generating Models in a Modular MVC

As a modular example we combine the ["Real World Example" code](http://www.doctrine-project.org/projects/orm/1.2/docs/manual/real-world-examples/en)
from the Doctrine manual into two modules. We will implement a user-management and a forum in two modules. Our default
module contains the user-management and our second module contains the forum code. Here is the modified YAML.

> **Note**
>
> Note the Default_Model_ and Forum_Model_ prefixes here which are necessary to comply with Zend Framework standards.

First the `application/configs/schema/user.yml`:

    ---
    Default_Model_User:
      columns:
        username: string(255)
        password: string(255)
      relations:
        Roles:
          class: Default_Model_Role
          refClass: Default_Model_UserRole
          foreignAlias: Users
          local: user_id
          foreign: role_id
        Permissions:
          class: Default_Model_Permission
          refClass: Default_Model_UserPermission
          foreignAlias: Users
          local: user_id
          foreign: permission_id

    Default_Model_Role:
      columns:
        name: string(255)
      relations:
        Permissions:
          class: Default_Model_Permission
          refClass: Default_Model_RolePermission
          foreignAlias: Roles
          local: role_id
          foreign: permission_id

    Default_Model_Permission:
      columns:
        name: string(255)

    Default_Model_RolePermission:
      columns:
        role_id:
          type: integer
          primary: true
        permission_id:
          type: integer
          primary: true
      relations:
        Role:
          class: Default_Model_Role
          local: role_id
        Permission:
          class: Default_Model_Permission
          local: permission_id

    Default_Model_UserRole:
      columns:
        user_id:
          type: integer
          primary: true
        role_id:
          type: integer
          primary: true
      relations:
        User:
          class: Default_Model_User
          local: user_id
        Role:
          class: Default_Model_Role
          local: role_id

    Default_Model_UserPermission:
      columns:
        user_id:
          type: integer
          primary: true
        permission_id:
          type: integer
          primary: true
      relations:
        User:
          class: Default_Model_User
          local: user_id
        Permission:
          class: Default_Model_Role
          local: permission_id

And the second file put into `application/configs/schema/forum.yml`:

    ---
    Forum_Model_Category:
      columns:
        root_category_id: integer(10)
        parent_category_id: integer(10)
        name: string(50)
        description: string(99999)
      relations:
        Subcategory:
          class: Forum_Model_Category
          local: parent_category_id
          foreign: id
        Rootcategory:
          class: Forum_Model_Category
          local: root_category_id
          foreign: id

    Forum_Model_Board:
      columns:
        category_id: integer(10)
        name: string(100)
        description: string(5000)
      relations:
        Category:
          class: Forum_Model_Category
          local: category_id
          foreign: id
        Threads:
          class: Forum_Model_Thread
          local: id
          foreign: board_id

    Forum_Model_Entry:
      columns:
        topic: string(100)
        message: string(99999)
        parent_entry_id: integer(10)
        thread_id: integer(10)
        date: integer(10)
      relations:
        Author:
          class: Model_User
          local: author_id
          foreign: id
        Parent:
          class: Forum_Model_Entry
          local: parent_entry_id
          foreign: id
        Thread:
          class: Forum_Model_Thread
          local: thread_id
          foreign: id

    Forum_Model_Thread:
      columns:
        board_id: integer(10)
        updated: integer(10)
        closed: integer(1)
      relations:
        Board:
          class: Forum_Model_Board
          local: board_id
          foreign: id
        Entries:
          class: Forum_Model_Entry
          local: id
          foreign: thread_id

Now if we put both files into `application/configs/schema` we can generate the model classes:

    zf generate-models-from-yaml doctrine

We get an error:

                          An Error Has Occurred
     Unknown Zend Controller Module 'forum' inflected from model class
    'Forum_Model_Category'. Have you configured your front-controller to
    include modules?

We have to generate the modules:

    zf create module default
    zf create module forum

Now edit the application.ini and replace the entry `resources.frontcontroller.controllerDirectory` with:

    resources.frontcontroller.moduleDirectory = APPLICATION_PATH "/modules"

For the "Zend Project Style" using the models/ directories we now need to instantiate module loaders,
here is a generic approach to this problem:

    class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
    {
        public function _initModuleLoaders()
        {
            $this->bootstrap('Frontcontroller');

            $fc = $this->getResource('Frontcontroller');
            $modules = $fc->getControllerDirectory();

            foreach ($modules AS $module => $dir) {
                $moduleName = strtolower($module);
                $moduleName = str_replace(array('-', '.'), ' ', $moduleName);
                $moduleName = ucwords($moduleName);
                $moduleName = str_replace(' ', '', $moduleName);

                $loader = new Zend_Application_Module_Autoloader(array(
                    'namespace' => $moduleName,
                    'basePath' => realpath($dir . "/../"),
                ));
            }
        }
    }

For the other two project styles you have to make sure that the `Zend_Loader_Autoloader` or any
other PSR-0 compatible loader points to the `library` directories correctly.

## Using Migrations

Doctrine 1 comes with a powerful migration support, which can also accessed from the Zend Tool
providers.

You can show the current migration version:

    zf show-migration doctrine

You can generate new migrations either by creating a bare class:

    zf generate-migration doctrine --class-name=MyMigration

From a database-diff:

    zf generate-migration doctrine --from-database

From a model diff:

    zf generate-migration doctrine --from-models

To migrate your database you can either specify a version to migrate to:

    zf excecute-migration doctrine --to-version=123

Or don't specify a version and migrate to the latest:

    zf excecute-migration doctrine

## Paginator Adapter

ZFDoctrine offers a pagination adapter for `Zend_Paginator` that makes `Doctrine_Query` instances
paginateable:

    $query = Doctrine_Query::create(...)
    $adapter = new ZFDoctrine_Paginator_Adapter($query);
    $paginator = new Zend_Paginator($adapter);

## Dynamic Form Generation

For admin purposes its often necesary and cumbersome to implement lots of forms that
allow to add or edit database records. Using the `ZFDoctrine_Form` component you can
easily generate simple and more complex forms that directly work on `Doctrine_Record`
instances. These forms are dynamically generated upon request.

The very simplest form would look like this:

    $form = new ZFDoctrine_From_Model(array(
        'model' => 'Default_Model_User',
        'action' => '.',
        'method' => 'post'
    ));

This would give you a class with the same interface as Zend_Form, but it would automatically
get fields from the model called `Default_Model_User`. Having the same interface as Zend_Form means that
you can essentially use the class just like any other Zend_Form instance; it just has some more magic. :)

### Basic usage: Creating new records

    //ExampleController.php
    class ExampleController extends Zend_Controller_Action
    {
        public function createAction()
        {
            $form = new ZFDoctrine_From_Model(array(
                'model' => 'Default_Model_User',
                'action' => '.',
                'method' => 'post'
            ));

            if($this->getRequest()->isPost() && $form->isValid($_POST)) {
                // This saves the form's data to the DB.
                // $record will be the new model instance created when saving.
                $record = $form->save();

                //redirect elsewhere after completion
                $this->_helper->redirect('update', 'example');
            }

            //assign the form to the view
            $this->view->form = $form;
        }
    }

The view script for `formAction` looks plain simple:

    <h2>Fill this form</h2>
    <?= $this->form; ?>

The formAction method is a pretty typical form process action: it creates a form and validates it if the request
was a form submission, or displays it if it wasn’t or something was not valid.

### Editing existing records

Editing existing records is similar to what seen above, but we must first load a record and assign it to the form
before rendering, validating or saving:

    public function editUserAction()
    {
        $userId = $this->_getParam('id');
        $form = new ZFDoctrine_Form_Model(array(
            'model' => 'Default_Model_User',
            'action' => '/zf-doctrine/index/edit-user/id/'.$userId,
            'method' => 'post',
        ));

        $user = Doctrine::getTable('Default_Model_User')->find($userId);
        $form->setRecord($user);

        if($this->getRequest()->isPost() && $form->isValid($_POST)) {
            $record = $form->save();

            // redirect elsewhere after completion
            $this->_redirect('/');
        }

        //assign the form to the view
        $this->view->form = $userId;
        $this->view->form = $form;
    }

After using setRecord, the default field values will be read from the record passed to the method. Also, when calling
save(), any modifications will be saved to this record instead of a new one.

### Modifying the form behavior

The class in its current implementation supports both creating new records and editing existing ones, and it can also display some relations as select boxes and subforms. You can also make it ignore chosen columns so it won’t autogenerate fields for them and such. Other options include giving labels for fields and switching their types.
Advanced settings example

    <?php
    class AdvancedForm extends ZFDoctrine_Form_Model
    {
        protected $_model = 'Article';

        // By default, many-relations will be generated, but you can disable them.
        protected $_generateManyFields = true;

        // Let's ignore these two fields
        protected $_ignoreFields = array('created_at', 'updated_at');

        // Make the content column's field type 'textarea' instead of the default 'text'
        protected $_fieldTypes = array(
            'content' => 'textarea'
        );

        // Give some human-friendly labels for the fields:
        protected $_fieldLabels = array(
            'name' => 'Article name',
            'content' => 'Article content',
            'category_id' => 'Category'
        );

        protected function _preGenerate()
        {
            // this method is called before the form is generated
        }
        
        protected function _postGenerate()
        {
            // this method is called after the form is generated
        }
    }

In the above snippet you can see all the configuration options and the two event methods. Most of the variables should
be quite self-explanatory.