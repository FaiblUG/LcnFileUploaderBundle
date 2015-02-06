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
     * @param $entityId
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function createOrEditAction(Request $request, $entityId = null)
    {
        $fileUploader = $this->container->get('lcn.file_uploader');

        $editId = intval($entityId);
        if (empty($editId)) {
            $editId = intval($request->get('editId', mt_rand(100000000000, 999999999999)));
            if ($editId < 100000000000) {
                throw new \Exception('invalid editId');
            }
        }

        $uploadFolderName = $this->getUploadFolderName($editId);

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
                    $entityId = $editId; //in a real world scenario you would use the id of your jsut persisted entity
                }

                $fileUploader->syncFilesFromTemp($uploadFolderName);

                return $this->redirect($this->generateUrl('lcn_file_uploader_demo_show', array('entityId'  => $entityId)));
            }
        } else {
            if ($entityId) {
                $fileUploader->syncFilesToTemp($uploadFolderName);
            }
        }

        return $this->render('LcnFileUploaderBundle:Demo:edit.html.twig', array(
            'entityId' => $entityId,
            'form' => $form->createView(),
            'uploadUrl' => $this->generateUrl('lcn_file_uploader_demo_handle_file_upload', array('editId'  => $editId)),
            'uploadFolderName' => $uploadFolderName,
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
            'folder' => $this->getUploadFolderName($editId),
            //'max_number_of_files' => 1, //overwrites parameter lcn_file_uploader.max_number_of_files
            //'allowed_extensions' => array('zip', 'rar', 'tar', 'gz'), //overwrites parameter lcn_file_uploader.allowed_extensions
            //'sizes' => array('thumbnail' => array('folder' => 'thumbnail', 'max_width' => 100, 'max_height' => 100, 'crop' => true), 'profile' => array('folder' => 'profile', 'max_width' => 400, 'max_height' => 400, 'crop' => true)), //overwrites parameter lcn_file_uploader.sizes
        ));
    }

    /**
     * Get the upload folder name for the given entity id.
     * This is where the uploaded files will be persisted to.
     *
     * @param $id
     * @return string
     */
    private function getUploadFolderName($id)
    {
        return 'demo/' . $id;
    }
```

#### In Your Layout

Include these stylesheets and scripts in your html document:

```html
    <link rel="stylesheet" href="{{ asset('bundles/lcnfileuploader/dist/main.css') }}">
    <link rel="stylesheet" href="{{ asset('bundles/lcnfileuploader/dist/theme.css') }}">
    <script src="{{ asset('bundles/lcnfileuploader/dist/main.js') }}"></script>
```
    
Or you can use assetic in your twig template:

```twig
{% extends 'base.html.twig' %}

{% block stylesheets %}
    {{ parent() }}
    <link rel="stylesheet" href="{{ asset('bundles/lcnfileuploader/dist/main.css') }}">
    <link rel="stylesheet" href="{{ asset('bundles/lcnfileuploader/dist/theme.css') }}">
{% endblock %}
{% block javascripts %}
    {{ parent() }}
    <script src="{{ asset('bundles/lcnfileuploader/dist/main.js') }}"></script>
{% endblock %}
```

The exact position and order does not matter. However, for best performance you should include the link tags in your head section and the script tag right before the closing body tag.

#### In the Edit Template
====================

Now include the upload widget anywhere on your page:

```twig
{% include 'LcnFileUploaderBundle:Theme:lcnFileUploaderWidget.html.twig' with {
    'uploadUrl': uploadUrl,
    'tempUploadFolderName': tempUploadFolderName,
    'formSelector': '#lcn-file-uploader-demo'
} %}
```
    
Full example:

```twig
{{ form_start(form, { 'attr': { 'id': 'lcn-file-uploader-demo' } }) }}

    {{ form_errors(form) }}

    {% include 'LcnFileUploaderBundle:Theme:lcnFileUploaderWidget.html.twig' with {
        'uploadUrl': uploadUrl,
        'uploadFolderName': uploadFolderName,
        'formSelector': '#lcn-file-uploader-demo'
    } %}

    {{ form_rest(form) }}
</form>
```


### Retrieving existing Uploads

#### Retrieve File Names ####

You can easily obtain a list of the names of all files stored in a given folder:

```php
$fileUploader = $this->container->get('lcn.file_uploader');
$filenames = $fileUploader->getFilenames('demo/' . $entity->getId()));
```

However, there is a performance cost associated with accessing the filesystem.
If you run into performance problems you might want to keep a list of attachments in a Doctrine table or some cache layer.  


#### Retrieve File URLs ####

You can easily obtain a list of urls of all files stored in a given folder:

```php
$fileUploader = $this->container->get('lcn.file_uploader');
$fileUrls = $fileUploader->getFileUrls('demo/' . $entity->getId());
```

However, there is a performance cost associated with accessing the filesystem.
If you run into performance problems you might want to keep a list of attachments in a Doctrine table or some cache layer.  


#### Retrieve Thumbnail URLs ####

If you are dealing with image uploads, you can pass a defined size name:

```php
$fileUploader = $this->container->get('lcn.file_uploader');
$fileUrls = $fileUploader->getFileUrls('demo/' . $entity->getId(), 'thumbnail');
```

The image sizes are defined as lcn_file_uploader.sizes parameter:

```yaml
  # Define sizes for image uploads
  lcn_file_uploader.sizes:
    # Depending on your further requirements, you might want to define additional image sizes.
    # However, more advanced solutions exist for image resampling, e.g.
    # https://github.com/liip/LiipImagineBundle.
    
    # required: "thumbnail" 
    thumbnail:
      folder: thumbnail
      max_width: 200
      max_height: 150
      crop: true
    # optional: "original" - define original image size if you want to restrict the maximum image dimensions:
    original:
      folder: original
      max_width: 3000
      max_height: 2250
      crop: false
```


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

### Removing Temporary Files

You should make sure that the temporary files do not eat up your storage.

The following Command Removes all temporary uploads older than 120 minutes

```sh
app/console lcn:file-uploader:cleanup --min-age-in-minutes=120
´´´

You might want to setup a cronjob that automatically executes that command in a given interval.



### More Configuration Parameters

Most of the options can be configured by overriding the parameters defined in `Resources/config/services.yml` in this bundle.


Limitations
===========

This bundle accesses the file system via the `glob()` function. It won't work out of the box with an S3 stream wrapper.

Syncing files back and forth to follow the editId pattern might not be agreeable if your attachments are very large. In that case, don't use the editId pattern. One alternative is to create objects immediately in the database and not show them in the list view until you mark them live. This way your edit action can use the permanent id of the object as part of the `folder` option, and nothing has to be synced. In this scenario you should probably move the attachments list below the form to hint to the user that there is no such thing as "cancelling" those actions.

