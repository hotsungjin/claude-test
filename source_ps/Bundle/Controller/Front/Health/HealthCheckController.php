<?php
namespace Bundle\Controller\Front\Health;


class HealthCheckController extends \Controller\Front\Controller
 {
     public function index()
     {
         http_response_code(200);
         print_r("OK");
         exit;
     }

 }


