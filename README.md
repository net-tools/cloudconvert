# net-tools/cloudconvert

## Composer library to interface with CloudConvert API

Cloudconvert is a web based service which converts files between multiple file formats. I personnaly use the CloudConvert API to transform PDF files to txt files (I can't search in a PDF file within PHP, but if I convert it to a plain text file, I may use any string search function).


### Setup instructions

To install net-tools/core package, just require it through composer : `require net-tools/cloudconvert:^1.0.0`.


### How to use ?

The Client class must be instantiated with an API key (get it from you CloudConvert account). Then, use any of the following method :
- listConversions
- deleteConversion
- deleteConversions
- convertDownload (the file to be converted is downloaded on your website)
- convertUploadData (the data to be converted is a string)
- convertUpload (the file to be converted is uploaded with the request)

For details about parameters and values returned, you may refer to the CloudConvert API, as this Client class in only a facade pattern, abstracting technicial details, such as performing the actual HTTP request, to the end-user.



### Sample

```php
// $APIKEY must contain the API key from your CloudConvert account
// $URL refers to a file hosted on your website will be downloaded 
// and converted (e.g. : http://mysite.info/test.pdf).
$client = new Nettools\CloudConvert\Client($apikey);
$client->convertDownload('pdf', 'txt', $url, array('download'=>'inline'));
// and also :
$client->convertUpload('pdf', 'txt', '/home/tmp/mypdf.pdf', array('download'=>'inline'));
```


## API Reference

To read the entire API reference, please refer to the PHPDoc here : 
http://net-tools.ovh/api-reference/net-tools/Nettools/CloudConvert.html
