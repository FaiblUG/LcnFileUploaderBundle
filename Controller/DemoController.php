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
            'folder' => $this->getTempUploadFolderNameForEditId($editId)
            //'sizes' => $this->getSizesConfig($galleryName),
            //'max_number_of_files' => $this->getMaxNumberOfFilesConfig($galleryName),
            //'allowed_extensions' => $this->allowedExtensions,
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
