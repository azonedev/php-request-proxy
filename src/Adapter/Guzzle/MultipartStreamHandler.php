<?php

namespace Nahid\RequestProxy\Adapter\Guzzle;

use GuzzleHttp\Psr7\MultipartStream;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;

class MultipartStreamHandler
{
    public function __invoke(callable $handler)
    {
        return function(ServerRequestInterface $request, array $options) use ($handler){
            $contentType = $request->getHeader('Content-Type')[0] ?? 'plain/text';

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

            if (is_array($file)) {
                foreach($file as $key => $value) {
                    $fileName .= $name . '[' . $key . ']';

                    $elements[] = $this->makeFileElements($fileName, $value);
                    $fileName = '';
                    continue;

                }
            } else {
                $fileName .= $name;
                $elements[] = $this->makeFileElements($fileName, $file);
            }


            $fileName = '';

        }

        return $elements;
    }

    protected function makeFileElements(string $fileName, UploadedFileInterface $file): array
    {
            return [
                'name' => $fileName,
                'contents' => $file->getStream(),
                'filename' => $file->getClientFilename(),
                'headers' => [
                    'Content-Type' => 'image/jpg'
                ]
            ];
    }

    protected function postFields(array $postFields, $fieldName = '', $elements = []) {
        foreach($postFields as $name => $postField){
            if (is_array($postField)) {
               $elements = $this->makeArrayPostFields($postField, $name, $elements);
            }else {
                $elements[] = $this->makePostFieldsElement($name, $postField);
            }
        }

        return $elements;
    }

    protected function makeArrayPostFields(array $postFields, string $fieldName, array $elements): array
    {
        foreach ($postFields as $key => $value){
            $name = $fieldName . '[' . $key . ']';
            if(is_string($value)){
                $elements[] = $this->makePostFieldsElement($name, $value);
            }else{
                $elements = $this->makeArrayPostFields($value, $name, $elements);
            }
            $name = '';
        }
        return $elements;
    }

    protected function makePostFieldsElement(string $name, string $content): array
    {
        return [
            'name' => $name,
            'contents' => $content
        ];
    }

}
