<?php

if(!empty($argv)){
    $zip_name = $argv[2];
    $proj_id = $argv[1];
    $name = "http://gitlab.simplyhq.com/api/v4/projects/$proj_id/repository/archive.zip?sha=$zip_name";
    $result = downloadZipFile($name, 'test.zip');
    die();
}

elseif (isset($_POST['name'])){
    $name = $_POST['name'];
    $result = downloadZipFile($name, 'test.zip');
    die(json_encode(array('file' => $result)));
}



function downloadZipFile($url, $filepath)
{
    $header = array('PRIVATE-TOKEN: S2rNfKstFUgzWwC1U-Uf');
    $ch = curl_init();
    $fp = fopen($filepath, 'w+');

    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FILE, $fp);

    curl_setopt($ch, CURLOPT_URL, $url);

    $raw_file_data = curl_exec($ch);

    if (false) {
        echo 'error:' . curl_error($ch);
    } else {
        fclose($fp);

        $zip = new ZipArchive;
        $zip->open('test.zip');

        $ff = $zip->getNameIndex(0);
        $foldName = substr($ff, 0, -1);
        $zip->extractTo('gitlab_archives/');
        $zip->close();


        $pathdir = realpath('gitlab_archives/' . $foldName);
        if(!is_dir("new_archives")) {
            mkdir("new-archives", 0777);
        };
        $nameArchive = "new-archives/$foldName.zip";
        $zip2 = new ZipArchive;
        if ($zip2->open($nameArchive, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {


            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($pathdir),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $name => $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($pathdir) + 1);

                    $zip2->addFile($filePath, $relativePath);

                }
            }
            $resp = $zip2->close();

            function removeDirectory($folder)
            {
                if (is_dir($folder) === true) {
                    $folderContents = scandir($folder);
                    unset($folderContents[0], $folderContents[1]);
                    foreach ($folderContents as $content => $contentName) {
                        $currentPath = $folder . '/' . $contentName;
                        $filetype = filetype($currentPath);
                        if ($filetype == 'dir') {
                            removeDirectory($currentPath);
                        } else {
                            unlink($currentPath);
                        }
                        unset($folderContents[$content]);
                    }
                    rmdir($folder);
                }
            }

            removeDirectory($pathdir);

            unlink('test.zip');
        }
        $ready_message = "New zip archive was created in \"$nameArchive\"";
        $filename= "$nameArchive";
        if(!empty($argv)){
            echo "\033[32m". "\n\"$ready_message\n";
        }

        return $filename;


    }
    curl_close($ch);


}