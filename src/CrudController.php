<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use App\Http\Requests;
use App\Http\Controllers\Controller;

abstract class CrudController extends Controller
{
    protected $model;
    protected $view;

    /**
     * Obtem o recurso.
     *
     * @author Marcelo Burkard <marcelo.burkard@meta.com.br>
     * @since  10/04/2017
    *
     * @return Illuminate\Database\Eloquent\Model $model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Salva o recurso.
     *
     * @author Marcelo Burkard <marcelo.burkard@meta.com.br>
     * @since  10/04/2017
     *
     * @param  Illuminate\Database\Eloquent\Model $model
     * @return CrudController
     */
    public function setModel($model)
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Obtem o recurso para visualização.
     * Se não houver view setada, retorna o model
     *
     * @author Marcelo Burkard <marcelo.burkard@meta.com.br>
     * @since  24/04/2017
    *
     * @return Illuminate\Database\Eloquent\Model $model
     */
    public function getView()
    {
        return $this->view ?? $this->getModel();
    }

    /**
     * Salva o recurso para visualização.
     *
     * @author Marcelo Burkard <marcelo.burkard@meta.com.br>
     * @since  24/04/2017
     *
     * @param  Illuminate\Database\Eloquent\Model $view
     * @return CrudController
     */
    public function setView($view)
    {
        $this->view = $view;

        return $this;
    }

    /**
     * Retorna JSON com a lista de resources.
     *
     * @author Marcelo Burkard <marcelo.burkard@meta.com.br>
     * @since  10/04/2017
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function index(Request $request)
    {
        return $this->getRequestModel($request)
                    ->paginate($request->input('per_page'));
    }

    /**
     * Constrói um select com a model atual baseado na request
     * passada como parâmetro.
     *
     * @author Alex Rohleder <alex.rohleder@meta.com.br>
     * @since  13/04/2017
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function getRequestModel(Request $request)
    {
        $where = $this->getWhere($request);
        list($field, $order) = explode('|', $request->input('sort', 'id|desc'));

        return $this->getView()
                    ->orderBy($field, $order)
                    ->where($where);
    }

    /**
     * Retorna array com atributos e valores para filtro
     *
     * @author Marcelo Burkard <marcelo.burkard@meta.com.br>
     * @since  10/04/2017
     *
     * @param  \Illuminate\Http\Request $request
     * @return Array
     */
    protected function getWhere(Request $request)
    {
        $filter = $request->input('filter', []);

        if (!is_array($filter)) {
            $filter = (array) json_decode($filter);
        }

        $where  = $this->getWhereRecursive($filter);

        return $where;
    }

    /**
     * Função genérica que implementa a função de busca LIKE %{}%
     * Retorna array para ser usado dentro do where() do Eloquent
     * Funciona apenas para atributos referentes à model padrão
     *
     * @todo Implementar de forma genérica a busca dentro de outras
     *       models relacionadas.
     *
     * @author Marcelo Burkard <marcelo.burkard@meta.com.br>
     * @since  11/04/2017
     *
     * @param  Array $array
     * @param  Array $where
     *
     * @return Array
     */
    protected function getWhereRecursive(array $array, array $where = [])
    {
        array_walk($array, function ($value, $key) use (&$where) {
            // Chaves estrangeiras são sempre numericas
            if ($this->endsWith($key, '_id')) {
                array_push($where, [$key, $value]);
            } elseif (!is_array($value) && $value != '') {
                // Executa query case insentive transformando strings em maiusculo
                $value = mb_strtoupper($value);
                array_push($where, [DB::raw("UPPER({$key})"), 'LIKE', "%{$value}%"]);
            }
        });

        return $where;
    }

    /**
     * Checa se uma string termina com uma outra string
     *
     * @param String $haystack
     * @param String $needle
     *
     * @return @bool
     */
    function endsWith($haystack, $needle) {
        $length = strlen($needle);

        return $length === 0 ||
        (substr($haystack, -$length) === $needle);
    }

    /**
     * Aplica as regras de validação e salva um novo
     * ou atualiza um recurso existente.
     *
     * @author Marcelo Burkard <marcelo.burkard@meta.com.br>
     * @since  10/04/2017
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function store(Request $request)
    {
        $this->validate($request, $this->getValidationRules());

        return $this->getModel()
                    ->updateOrCreate($request->only('id'), $request->except('id'));
    }

   /**
     * Retorna regras de validação no padrão do Laravel
     *
     * @author Marcelo Burkard <marcelo.burkard@meta.com.br>
     * @since  11/04/2017
     *
     * @return Array
     */
    protected function getValidationRules()
    {
        return [];
    }

    /**
     * Retorna o recurso especificado.
     *
     * @author Marcelo Burkard <marcelo.burkard@meta.com.br>
     * @since  10/04/2017
     *
     * @param  int $id
     * @return Array
     */
    public function show($id, Request $request)
    {
        return $this->getModel()->find($id);
    }

    /**
     * Deleta recurso no banco de dados
     *
     * @author Marcelo Burkard <marcelo.burkard@meta.com.br>
     * @since  10/04/2017
     *
     * @param  int $id
     * @return Array
     */
    public function destroy($id)
    {
        $isDeleteBlocked = $this->isDeleteBlocked($id);

        if (! $isDeleteBlocked) {
            try {
                $model = $this->getModel()
                              ->find($id)
                              ->delete();
            } catch (QueryException $error) {
                return response(['message' => 'Existem dependências deste registro.'], 500);
            }
        }

        return ['success' => $isDeleteBlocked];
    }

    /**
     * Verifica se a ação de deletar está bloqueada
     * Se sim, retorna array contendo atributos 'success' boolean e 'message' string explicando o erro
     * ['success' => false, 'message' => 'Explicação do erro']
     * Se não, retorna false
     *
     * @author Marcelo Burkard <marcelo.burkard@meta.com.br>
     * @since  10/04/2017
     *
     * @param  int $id
     * @return Bool/Array
     */
    protected function isDeleteBlocked($id)
    {
        return false;
    }

    /**
     * Retorna todos valores existentes na mocdel
     * Função utilizada para preecher options do vueselect
     *
     * Seleciona sempre o campo id
     * Caso tenha sido setado seleciona também uma coluna
     * Ambos podem receber aliases
     *
     * @author Marcelo Burkard <marcelo.burkard@meta.com.br>
     * @since  07/06/2017
     *
     * @param  \Illuminate\Http\Request  $request
     * @return Array
     */
    public function getOptions(Request $request)
    {
        $coluna = $request->input('coluna', 'id');

        $select = [];
        if ($coluna != 'id') {
            $colunaAlias = $request->input('colunaAlias', $coluna);
            $colunaRaw   = "DISTINCT({$coluna}) AS {$colunaAlias}";
            array_push($select, DB::raw($colunaRaw));
        }

        $idAlias = $request->input('idAlias', 'id');
        $idRaw   = "id AS {$idAlias}";
        array_push($select, DB::raw($idRaw));

        return $this->getView()
                    ->whereNotNull($coluna)
                    ->select($select)
                    ->orderBy($coluna, 'asc')
                    ->get();
    }

    public function getOptionsAtiva(Request $request)
    {
        $coluna = $request->input('coluna', 'id');

        $select = [];
        if ($coluna != 'id') {
            $colunaAlias = $request->input('colunaAlias', $coluna);
            $colunaRaw   = "DISTINCT({$coluna}) AS {$colunaAlias}";
            array_push($select, DB::raw($colunaRaw));
        }

        $idAlias = $request->input('idAlias', 'id');
        $idRaw   = "id AS {$idAlias}";
        array_push($select, DB::raw($idRaw));

        return $this->getView()
                    ->whereNotNull($coluna)
                    ->where('ativo', 1)
                    ->select($select)
                    ->orderBy($coluna, 'asc')
                    ->get();
    }
}
