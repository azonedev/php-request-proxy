<?php

namespace Nahid\RequestProxy\Adapter\Guzzle;

use GuzzleHttp\Psr7\MultipartStream;
use Laminas\Diactoros\UploadedFile;
use Psr\Http\Message\RequestInterface;

class MultipartStreamHandler
{
    public function __invoke(callable $handler)
    {
        return function(RequestInterface $request, array $options) use ($handler){
            $contentType = $request->getHeader('Content-Type')[0];
            if(!preg_match('#^multipart/form-data; boundary=(.*)#',$contentType,$matches)){
                return $handler($request,$options);
            }


            $boundary = $matches[1];
            $files = $request->getUploadedFiles();
            $postFields = $request->getParsedBody();

            $fields = $this->postFields($postFields, '', []);
            $files = $this->files($files, '', []);

            $elements = [ ... $files, ... $fields];

            $multiStream = new MultipartStream($elements,$boundary);
            $request = $request->withBody($multiStream);

            return $handler($request,$options);
        };
    }



    protected function files(array $files, $fileName = '', $elements = []) {
        foreach($files as $name => $file){
            if(empty($fileName)){
                $fileName .= $name;
            } else {
                $fileName .= '[' . $name . ']';
            }

            if (is_array($file)) {
                $elements = $this->files($file, $fileName, $elements);
            }

            /** @var UploadedFile $file */

            if($file instanceof UploadedFile) {
                $elements[] = [
                    'name' => $fileName,
                    'contents' => $file->getStream(),
                    'filename' => $file->getClientFilename(),
                ];

                $fileName = '';
            }

        }

        return $elements;
    }

    protected function postFields(array $postFields, $fieldName = '', $elements = []) {
        foreach($postFields as $name => $postField){
            if(empty($fieldName)){
                $fieldName .= $name;
            } else {
                $fieldName .= '[' . $name . ']';
            }

            if (is_array($postField)) {
                $elements = $this->postFields($postField, $fieldName, $elements);
            }

            if(is_string($postField)) {
                $elements[] = [
                    'name' => $fieldName,
                    'contents' => $postField,
                ];

                $fieldName = '';
            }
        }

        return $elements;
    }

}