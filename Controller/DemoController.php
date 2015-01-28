<?php

namespace Lcn\FileUploaderBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class DemoController extends Controller
{

    /**
     * Show Uploads for the given entity id.
     *
     * In a real world scenario you might want to check view permissions
     *
     * @param Request $request
     * @param $entityId
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function showAction(Request $request, $entityId)
    {
        $fileUploader = $this->container->get('lcn.file_uploader');

        $entityId = intval($entityId);
        if ($entityId < 100000000000) {
            throw new \Exception('invalid editId');
        }

        $uploadFolderName = $this->getUploadFolderName($entityId);
        $uploadedFiles = $fileUploader->getFileUrls($uploadFolderName);

        return $this->render('LcnFileUploaderBundle:Demo:show.html.twig', array(
            'entityId' => $entityId,
            'uploadedFiles' => $uploadedFiles,
        ));
    }

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
            //'sizes' => array('thumbnail' => array('folder' => 'thumbnails', 'max_width' => 100, 'max_height' => 100, 'crop' => true), 'profile' => array('folder' => 'profile', 'max_width' => 400, 'max_height' => 400, 'crop' => true)), //overwrites parameter lcn_file_uploader.sizes
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

}
