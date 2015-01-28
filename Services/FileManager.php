<?php

namespace Lcn\FileUploaderBundle\Services;

class FileManager
{

    /**
     * Get a list of files in the given directory
     */
    public function getFiles($directory)
    {
        if (file_exists($directory))
        {
            $dirs = glob("$directory/*");
            if (!is_array($dirs)) {
                $dirs = array();
            }
            $result = array_map(function($s) { return preg_replace('|^.+[\\/]|', '', $s); }, $dirs);
            return $result;
        }
        else
        {
            return array();
        }
    }

    /**
     * Remove the given directory
     *
     * @param string $uploadFolderName
     */
    public function removeFiles($directory)
    {
        system("rm -rf " . escapeshellarg($directory));
    }

    /**
     * Remove the given directory
     *
     * @param string $uploadFolderName
     */
    public function removeOldFiles($directory, $minAgeInMinutes)
    {
        system('find '.escapeshellarg($directory).' -mmin +'.escapeshellarg($minAgeInMinutes).' -type f -delete');
        system('find '.escapeshellarg($directory).' -mindepth 1 -type d -empty -delete');
    }

    /**
     * Sync existing files from one folder to another. The 'fromFolder' and 'toFolder'
     * options are required. As with the 'folder' option elsewhere, these are appended
     * to the file_base_path for you, missing parent folders are created, etc. If 
     * 'fromFolder' does not exist no error is reported as this is common if no files
     * have been uploaded. If there are files and the sync reports errors an exception
     * is thrown.
     * 
     * If you pass consistent options to this method and handleFileUpload with
     * regard to paths, then you will get consistent results.
     */
    public function syncFiles($options = array())
    {
        if (!strlen(trim($options['from_folder'])))
        {
            throw \Exception("from_folder option looks empty, bailing out");
        }
        if (!strlen(trim($options['to_folder'])))
        {
            throw \Exception("to_folder option looks empty, bailing out");
        }

        $from = $options['from_folder'];
        $to = $options['to_folder'];

        if (file_exists($from))
        {
            if (isset($options['create_to_folder']) && $options['create_to_folder'])
            {
                @mkdir($to, 0777, true);
            }
            elseif (!file_exists($to))
            {
                throw new \Exception("to_folder does not exist");
            }
            $result = null;
            system("rsync -a --delete " . escapeshellarg($from . '/') . " " . escapeshellarg($to), $result);
            if ($result !== 0)
            {
                throw new \Exception("Sync failed");
            }
            if (isset($options['remove_from_folder']) && $options['remove_from_folder'])
            {
                system("rm -rf " . escapeshellarg($from));
            }
        }
        else
        {
            // A missing from_folder is not an error. This is commonly the case
            // when syncing from something that has nothing attached to it yet, etc.
        }
    }
}
