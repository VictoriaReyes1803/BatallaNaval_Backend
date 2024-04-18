<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function __construct(Request $request) {
        App::setLocale('es');
        DB::enableQueryLog();
        $this->reqst = $request;
    }

    public function getQuery() {
        $collection = collect(DB::getQueryLog());
        $collection_last = $collection->last();
        $collection_renew = collect($collection_last);
        return $collection_renew->get('query', null);
    }

    protected $name;
    protected $ignore = false;
    protected $reqst;

    protected function createResponse(int $status, $data = null, $errors = null, $last_update = null) {
        $new_response = response()->json([
            'data' => $data,
            'message' => $this->getMsg($this->reqst->method(), $status, $data != null ? ($data instanceof Collection ? count($data) : 1) : 0),
            'query' => $this->getQuery(),
            'ignore' => $this->ignore,
            'last_update' => $last_update
        ], $status);

        if ($errors != null) {
            $new_response['errors'] = $errors;
        }
        return $new_response;
    }
    protected function setIgnore($ignore) {
        $this->ignore = $ignore;
    }

    protected function getName() {
        return $this->name;
    }

    protected function setName($name) {
        $this->name = $name;
    }

    protected function getMsg(string $method, int $status = 200, int $length = 0) {
        if ($status == 404) {
            return "Un registro de ".$this->getName()." no fue encontrado/a.";
        }
        switch ($method) {
            case 'GET':
                return ($length == 1 ? "Un registro de " : ($length == 0 ? "Ningún registro de " : "Todo los registros (".$length.") de " )).$this->getName().($length > 1 ? " fueron otorgados" : " fue otorgado." );
            case 'POST':
                return $this->getName()." fue creado/a.";
            case 'PUT':
            case 'PATCH':
                return "Un registro de ".$this->getName()." fue actualizado/a.";
            case 'DELETE':
                return "Un registro de ".$this->getName()." fue desactivado/a.";
        }
    }
}
