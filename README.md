LcnFileUploaderBundle
========================

Easy ajax file uploads for Symfony 2. MIT License.

Inspired by [punkave/symfony2-file-uploaderbundle](https://github.com/punkave/symfony2-file-uploader-bundle) (no longer maintained)


Introduction
------------

This bundle provides enhanced file upload widgets based on the [BlueImp jQuery file uploader](https://github.com/blueimp/jQuery-File-Upload/) package. Both drag and drop and multiple file selection are fully supported in compatible browsers.

The uploader delivers files to a folder that you specify. If that folder already contains files, they are displayed side by side with new files, as existing files that can be removed.

The bundle can automatically scale images to sizes you specify. The provided synchronization methods make it possible to create forms in which attached files respect "save" and "cancel" operations.


Installation
------------

### Step 1: Install dependencies

#### jQuery

Make sure that [jQuery](http://jquery.com/) is included in your html document:

```html
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
```

#### Underscore.js

Make sure that [Underscore.js](http://underscorejs.org/) is included in your html document

```html
<script src="//cdnjs.cloudflare.com/ajax/libs/underscore.js/1.7.0/underscore-min.js"></script>
```


### Step 2: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```bash
$ composer require locaine/lcn-file-uploader-bundle "~1.0"
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

### Step 3: Enable the Bundle

Then, enable the bundle by adding the following line in the `app/AppKernel.php`
file of your project:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...

            new Lcn\FileUploaderBundle\LcnFileUploaderBundle(),
        );

        // ...
    }

    // ...
}
```

Usage
-----

### Add/Edit/Remove uploads

#### Controller Code

Let's assume you have an editAction() method in a controller.
In this action you have a form in which you would like to include a list of attached files that work like other fields in the form: you can add and remove files but nothing permanent happens unless the user clicks "save".

The LcnFileUploader needs a unique folder for the files attached to a given object.
To accomplish this for new objects as well as existing objects, we suggest you follow the "editId pattern", in which a form is assigned a unique, random "editId" for its entire lifetime, including multiple passes of validation if necessary. This allows us to manage file uploads for new objects that don't have their own id yet.
Note that the editId you generate should be highly random to prevent users from gaining control of each other's attachments.

This code takes care of creating an editId on the first pass through the form and syncs existing files attached to an existing entity, if any.
The from_folder and to_folder objects specify directory names where the attached files will be stored.

Later we'll look at how the full path to these folders is determined.

Fetching $entity and validating that the user is allowed to edit that particular entity is up to you.

```php  
<?php

namespace Lcn\FileUploaderBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class DemoController extends Controller
{

    /**
     * Edit Uploads for the given entity id or create new entity with uploads.
     *
     * In a real world scenario you might want to check edit permissions
     *
     * @param Request $request
     * @param $userId
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function createOrEditAction(Request $request, $entityId = null)
    {
        $fileUploader = $this->container->get('lcn.file_uploader');

        $editId = intval($request->get('editId', mt_rand(100000000000, 999999999999)));
        if ($editId < 100000000000) {
            throw new \Exception('invalid editId');
        }

        $form = $this->createFormBuilder()
            ->setAction(($entityId ? $this->generateUrl('lcn_file_uploader_demo_edit', array('entityId'  => $entityId)) : $this->generateUrl('lcn_file_uploader_demo_create')).'?editId='.$editId)
            ->setMethod('POST')
            ->add('save', 'submit')
            ->add('editId', 'hidden')
            ->getForm();

        if ($request->getMethod() == 'POST') {
            $form->submit($request);

            if ($form->isValid()) {

                /**
                 * In a real world scenario you would probably also persist the changes to your entity ...
                 */

                if (!$entityId) {
                    $entityId = mt_rand(100000, 999999); //in a real world scenario you would use the id of your jsut persisted entity
                }

                $fileUploader->syncFiles(
                    array(
                        'from_folder' => $this->getTempUploadFolderNameForEditId($editId),
                        'to_folder' => $this->getUploadFolderNameForEntityId($entityId),
                        'remove_from_folder' => true,
                        'create_to_folder' => true,
                    )
                );

                return $this->redirect($this->generateUrl('lcn_file_uploader_demo_edit', array('entityId'  => $entityId)));
            }
        } else {
            if ($entityId) {
                $fileUploader->syncFiles(
                    array(
                        'from_folder' => $this->getUploadFolderNameForEntityId($entityId),
                        'to_folder' => $this->getTempUploadFolderNameForEditId($editId),
                        'create_to_folder' => true,
                    )
                );
            }
        }

        return $this->render('LcnFileUploaderBundle:Demo:index.html.twig', array(
            'entityId' => $entityId,
            'form' => $form->createView(),
            'uploadUrl' => $this->generateUrl('lcn_file_uploader_demo_handle_file_upload', array('editId'  => $editId)),
            'tempUploadFolderName' => $this->getTempUploadFolderNameForEditId($editId),
        ));
    }

    /**
     * Store the uploaded file.
     *
     * In a real world scenario you might probably want to check
     * if the user is allowed to store uploads for the given entity id.
     *
     * Delegates to LcnFileUploader which implements a REST Interface and handles file uploads as well as file deletions.
     *
     * This action must not return a response. The response is generated in native PHP by LcnFileUploader.
     *
     * @param Request $request
     * @param int $userId
     */
    public function handleFileUploadAction(Request $request, $editId)
    {
        $editId = intval($editId);
        if ($editId < 100000000000) {
            throw new AccessDeniedHttpException('Invalid edit id: '.$editId);
        }

        $this->container->get('lcn.file_uploader')->handleFileUpload(array(
            'folder' => $this->getTempUploadFolderNameForEditId($editId),
            //'max_number_of_files' => 1, //overwrites parameter lcn_file_uploader.max_number_of_files
            //'allowed_extensions' => array('zip', 'rar', 'tar'), //overwrites parameter lcn_file_uploader.allowed_extensions
            //'sizes' => array('thumbnail' => array('folder' => 'thumbnails', 'max_width' => 100, 'max_height' => 100, 'crop' => true), 'profile' => array('folder' => 'profile', 'max_width' => 400, 'max_height' => 400, 'crop' => true)), //overwrites parameter lcn_file_uploader.sizes
        ));
    }

    /**
     * Get the upload folder name for the given entity id.
     * This is where the uploaded files will be persisted to.
     *
     * @param $entityId
     * @return string
     */
    private function getUploadFolderNameForEntityId($entityId)
    {
        return 'lcn-file-uploader-demo/' . $entityId;
    }

    /**
     * Get the upload folder name for the given demo user id.
     * This is where the uploaded files will be stored temporarily
     * until the user clicks the save button.
     *
     * @param $editId
     * @return string
     */
    private function getTempUploadFolderNameForEditId($editId)
    {
        return 'temp-lcn-file-uploader-demo/' . $editId;
    }

}
```

#### In Your Layout

Include these stylesheets and scripts in your html document:

    <link rel="stylesheet" href="{{ asset('bundles/lcnfileuploader/dist/main.css') }}">
    <link rel="stylesheet" href="{{ asset('bundles/lcnfileuploader/dist/theme.css') }}">
    <script src="{{ asset('bundles/lcnfileuploader/dist/main.js') }}"></script>

The exact position and order does not matter. However, for best performance you should include the link tags in your head section and the script tag right before the closing body tag.

#### In the Edit Template
====================

Now include the upload widget anywhere on your page:

```twig
{% include 'LcnFileUploaderBundle:Default:uploadFormWidget.html.twig' with {
    'uploadUrl': uploadUrl,
    'tempUploadFolderName': tempUploadFolderName,
    'formSelector': '#lcn-file-uploader-demo'
} %}
```
    
Full example:

```twig
{{ form_start(form, { 'attr': { 'id': 'lcn-file-uploader-demo' } }) }}

    {{ form_errors(form) }}

    {% include 'LcnFileUploaderBundle:Default:uploadFormWidget.html.twig' with {
        'uploadUrl': uploadUrl,
        'tempUploadFolderName': tempUploadFolderName,
        'formSelector': '#lcn-file-uploader-demo'
    } %}

    {{ form_rest(form) }}
</form>
```


### Retrieving existing Uploads

You can easily obtain a list of the names of all files stored in a given folder:

    $fileUploader = $this->container->get('lcn.file_uploader');
    $files = $fileUploader->getFiles(array('folder' => 'lcn-file-uploader-demo/' . $entity->getId()));

However, there is a performance cost associated with accessing the filesystem.
If you run into performance problems you might want to keep a list of attachments in a Doctrine table or some cache layer.  



### Advanced Usage

#### Setting the allowed file types

You can specify custom file types to divert from the default ones (which are defined in Resources/config/services.yml) by either specifying them in your handleFileUploadAction method or in parameters.yml.

***Per Widget in corresponding handleFileUploadAction:***
    $this->get('lcn.file_uploader')->handleFileUpload(array(
        'folder' => 'temp-lcn-file-uploader-demo/' . $editId,
        'allowed_extensions' => array('zip', 'rar', 'tar')
    ));


***Globally in parameters.yml:***
If you have the Symfony standard edition installed you can specify them in app/config/parameters.yml:

    file_uploader.allowed_extensions:
        - zip
        - rar
        - tar

#### Removing Files

When an entity gets deleted you should delete all of the attachments, as well.

You can do this as follows:

    $this->get('lcn.file_uploader'')->removeFiles(array('folder' => 'lcn-file-uploader-demo/' . $entity->getId()));

You probably might want to do that in a doctrine lifecycle preRemove event listener.

Removing Temporary Files
========================

If you choose to follow our editId pattern, you'll want to purge contents of web/uploads/temp-lcn-file-uploader-demo that are over a certain age on a periodic basis. People walk away from websites a lot, so not everyone will click your thoughtfully provided "cancel" action that calls removeFiles() based on the editId pattern.

Consider installing this shell script as a cron job to be run nightly. This shell script deletes files more than one day old, then deletes empty folders:

    #!/bin/sh
    find /path/to/my/project/web/uploads/temp-lcn-file-uploader-demo -mtime +1 -type f -delete
    find /path/to/my/project/web/uploads/temp-lcn-file-uploader-demo -mindepth 1 -type d -empty -delete

(Since the second command is not recursive, the parent folders may stick around an extra day, but they are removed the next day.)

Configuration Parameters
========================

See `Resources/config/services.yml` in this bundle. You can easily decide what the parent folder of uploads will be and what file extensions are accepted, as well as what sizes you'd like image files to be automatically scaled to.

The `from_folder`, `to_folder`, and `folder` options seen above are all appended after `file_uploader.file_base_path` when dealing with files.

If `file_uploader.file_base_path` is set as follows (the default):

    file_uploader.file_base_path: "%kernel.root_dir%/../web/uploads"

And the `folder` option is set to `lcn-file-uploader-demo/5` when calling `handleFileUpload`, then the uploaded files will arrive in:

    /root/of/your/project/web/uploads/lcn-file-uploader-demo/5/originals

If the only attached file for this posting is `botfly.jpg` and you have configured one or more image sizes for the `file_uploader.sizes` option (by default we provide several useful standard sizes), then you will see:

    /root/of/your/project/web/uploads/photos/5/originals/botfly.jpg
    /root/of/your/project/web/uploads/photos/5/thumbnail/botfly.jpg
    /root/of/your/project/web/uploads/photos/5/medium/botfly.jpg
    /root/of/your/project/web/uploads/photos/5/large/botfly.jpg

So all of these can be readily accessed via the following URLs:

    /uploads/photos/5/originals/botfly.jpg

And so on.

The original names and file extensions of the files uploaded are preserved as much as possible without introducing security risks.

Limit number of uploads
-----------------------

You can limit the number of uploaded files by setting the `max_no_of_files` property. You could set this in parameters.yml like this:

    parameters:
      file_uploader.max_number_of_files: 4

You'll probably want to add an error handler for this case. In the template where you initialize LcnFileUploader set `errorCallback`

    // Enable the file uploader
    $(function() {
      new LcnFileUploader({
        // ... other required options,

        'errorCallback': function(errorObj) {
          if (errorObj.error == 'maxNumberOfFiles') {
            alert("Maximum uploaded files exceeded!");
          }
        }
      });
    });



Limitations
===========

This bundle accesses the file system via the `glob()` function. It won't work out of the box with an S3 stream wrapper.

Syncing files back and forth to follow the editId pattern might not be agreeable if your attachments are very large. In that case, don't use the editId pattern. One alternative is to create objects immediately in the database and not show them in the list view until you mark them live. This way your edit action can use the permanent id of the object as part of the `folder` option, and nothing has to be synced. In this scenario you should probably move the attachments list below the form to hint to the user that there is no such thing as "cancelling" those actions.

Notes
=====

The uploader has been styled using Bootstrap conventions. If you have Bootstrap in your project, the uploader should look reasonably pretty out of the box.

The "Choose Files" button allows multiple select as well as drag and drop.
