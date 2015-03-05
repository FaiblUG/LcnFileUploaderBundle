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
     * Create temporary Demo entity id and redirect to edit page
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function createAction(Request $request)
    {
        $entityId = mt_rand(100000000000, 999999999999);

        return $this->redirect($this->generateUrl('lcn_file_uploader_demo_edit', array('entityId' => $entityId)));
    }

    /**
     * Edit Uploads for the given entity id
     *
     * In a real world scenario you might want to check edit permissions
     *
     * @param Request $request
     * @param $entityId
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $entityId)
    {
        $editId = intval($entityId);
        if ($editId < 100000000000) {
            throw new \Exception('invalid editId');
        }

        $fileUploader = $this->container->get('lcn.file_uploader');
        $uploadFolderName = $this->getUploadFolderName($editId);

        $form = $this->createFormBuilder()
            ->setAction($this->generateUrl('lcn_file_uploader_demo_edit', array('entityId'  => $entityId)))
            ->setMethod('POST')
            ->getForm();

        if ($request->getMethod() == 'POST') {
            $form->submit($request);

            if ($form->isValid()) {
                $fileUploader->syncFilesFromTemp($uploadFolderName);

                return $this->redirect($this->generateUrl('lcn_file_uploader_demo_show', array('entityId'  => $entityId)));
            }
        } else {
            $fileUploader->syncFilesToTemp($uploadFolderName);
        }

        return $this->render('LcnFileUploaderBundle:Demo:edit.html.twig', array(
            'entityId' => $entityId,
            'form' => $form->createView(),
            'uploadUrl' => $this->generateUrl('lcn_file_uploader_demo_handle_file_upload', array('entityId' => $entityId)),
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
    public function handleFileUploadAction(Request $request, $entityId)
    {
        $entityId = intval($entityId);
        if ($entityId < 100000000000) {
            throw new AccessDeniedHttpException('Invalid edit id: '.$entityId);
        }

        $this->container->get('lcn.file_uploader')->handleFileUpload(array(
            'folder' => $this->getUploadFolderName($entityId),
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
        //include two characters of hash to avoid file system / operating system restrictions
        //with too many files/directories within a single directory.
        return 'demo/' . substr(md5($id), 0, 2) . '/' . $id;
    }

}
