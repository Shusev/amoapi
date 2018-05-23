<?php
/**
 * amoCRM API client Base service
 */
namespace Ufee\Amo\Base\Services;
use Ufee\Amo\Base\Collections\Collection;

class LimitedList extends Cached
{
	protected
		$entity_collection = '\Ufee\Amo\Base\Collections\ApiModelCollection',
		$limit_rows_add = 300,
		$limit_rows_update = 300,
		$limit_rows = 500,
		$max_rows = 0,
		$methods = [
			'list', 'add', 'update'
		];
	
    /**
     * Service on load
	 * @return void
     */
	protected function _boot()
	{
		$this->api_args = [
			'USER_LOGIN' => $this->instance->getAuth('login'),
			'USER_HASH' => $this->instance->getAuth('hash'),
		];
	}

    /**
     * Create new model
	 * @param mixed $id
	 * @returm Model
     */
	public function create($id = null)
	{
		$model_class = $this->entity_model;
		$data = [
			'request_id' => mt_rand(), 
			'account_id' => $this->instance->getAuth('id')
		];
		if (is_numeric($id)) {
			$data['id'] = $id;
		}
		$model = new $model_class($data, $this);
		return $model;
	}

    /**
     * Add models to CRM
	 * @param mixed $models
     */
	public function add(&$models)
	{
		$create_models = $models;
		if (!is_array($models)) {
			$create_models = [$models];
		}
		$create_parts = [];
        $p = 0;
        $i = 1;
		foreach ($create_models as $create_model) {
            $create_parts[$p][] = $create_model;
            if ($i == $this->limit_rows_add) {
                $i = 1;
                $p++;
            } else {
                $i++;
            }
		}
		$added_raws = new Collection();
		foreach ($create_parts as $part) {
			$added_part = $this->_add($part);
			$added_raws->merge($added_part);
		}
		$added = true;
		foreach ($create_models as &$model) {
			if ($added_raw = $added_raws->find('request_id', $model->request_id)->first()) {
				$model->setId($added_raw->id);
				$model->setQueryHash($added_raw->query_hash);
				$model->saved();
			} else {
				$added = false;
			}
		}
		if (!is_array($models)) {
			if (!isset($create_models[0])) {
				throw new \Exception('Error: empty created models');
			}
			$models = $create_models[0];
		} else {
			$models = $create_models;
		}
		return $added;
	}

    /**
     * Add models part to CRM
	 * @param mixed $create_part
	 * @return Collection
     */
	protected function _add($create_part)
	{
		$raws = [];
		foreach ($create_part as $model) {
			$raw = [
				'request_id' => $model->request_id,
			];
			if (!$model instanceof \Ufee\Amo\Base\Models\ApiModel) {
				throw new \Exception('Error, adding models must be ApiModel instance');
			}
			foreach (static::$_require['add'] as $rfield) {
				if (is_null($model->$rfield)) {
					throw new \Exception('Error, field "'.$rfield.'" is required in '.$model::getBasename());
				}
				if (!$model->hasChanged($rfield)) {
					$raw[$rfield] = $model->$rfield;
				}
			}
			$raws[]= array_merge($raw, $model->getChangedRawApiData());
		}
		return $this->add->add($raws);
	}

    /**
     * Update models in CRM
	 * @param mixed $models
     */
	public function update(&$models)
	{
		$update_models = $models;
		if (!is_array($models)) {
			$update_models = [$models];
		}
		$update_parts = [];
        $p = 0;
        $i = 1;
		foreach ($update_models as $update_model) {
            $update_parts[$p][] = $update_model;
            if ($i == $this->limit_rows_update) {
                $i = 1;
                $p++;
            } else {
                $i++;
            }
		}
		$updated_raws = new Collection();
		foreach ($update_parts as $part) {
			$updated_part = $this->_update($part);
			$updated_raws->merge($updated_part);

		}
		$updated = true;
		foreach ($update_models as &$model) {
			if ($updated_raw = $updated_raws->find('id', $model->id)->first()) {
				$model->setId($updated_raw->id);
				$model->setQueryHash($updated_raw->query_hash);
				$model->saved();
			} else {
				$updated = false;
			}
		}
		if (!is_array($models)) {
			if (!isset($update_models[0])) {
				throw new \Exception('Error: empty updated models');
			}
			$models = $update_models[0];
		} else {
			$models = $update_models;
		}
		return $updated;
	}

    /**
     * Update models part in CRM
	 * @param mixed $update_part
	 * @return Collection
     */
	protected function _update($update_part)
	{
		$raws = [];
		foreach ($update_part as $model) {
			$raw = [];
			if (!$model instanceof \Ufee\Amo\Base\Models\ApiModel) {
				throw new \Exception('Error, updating models must be ApiModel instance');
			}
			foreach (static::$_require['update'] as $rfield) {
				if (is_null($model->$rfield)) {
					throw new \Exception('Error, field "'.$rfield.'" is required in '.$model::getBasename());
				}
				if (!$model->hasChanged($rfield)) {
					$raw[$rfield] = $model->$rfield;
				}
			}
			$raws[]= array_merge($raw, $model->getChangedRawApiData());
		}
		return $this->update->update($raws);
	}
	
    /**
     * Request arg set
	 * @param string $key
	 * @param mixed $value
     */
    public function where($key, $value = null)
    {
		return $this->list->where($key, $value);
	}
	
    /**
     * Set limit rows
	 * @param integer $count
	 * @return Service
     */
	public function limitRows($count)
	{
		$this->limit_rows = (int)$count;
		return $this;
	}
	
    /**
     * Set max rows
	 * @param integer $count
	 * @return Service
     */
	public function maxRows($count)
	{
		$this->max_rows = (int)$count;
		return $this;
	}
	
    /**
     * Get model list
	 * @return Collection
     */
	public function list()
	{
		return $this->list->recursiveCall();
	}
	
    /**
     * Get models by id
	 * @param integer|array $id
	 * @return Model|Collection
     */
	public function find($id)
	{
		$result = $this->list->where('limit_rows', is_array($id) ? count($id) : 1)
							 ->where('limit_offset', 0)
							 ->where('id', $id)
							 ->call();
		if (is_array($id)) {
			return $result;
		}
		if (!$model = $result->get(0)) {
			return null;
		}
		return $model;
	}
}