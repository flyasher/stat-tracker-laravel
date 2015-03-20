<?php

namespace PragmaRX\Tracker\Data\Repositories;

class Log extends Repository {

	private $currentLogId = null;

	public function updateRoute($route_path_id)
	{
		$model = $this->getModel();

		if ($model->id)
		{
			$model->route_path_id = $route_path_id;

			$model->save();
		}

		return $model;
	}

	public function updateError($error_id)
	{
		$model = $this->getModel();

		if ($model->id)
		{
			$model->error_id = $error_id;

			$model->save();
		}

		return $model;
	}

	public function bySession($sessionId, $results = true)
	{
		$query = $this
					->getModel()
					->where('session_id', $sessionId)->orderBy('updated_at', 'desc');

		if ($results)
		{
			return $query->get();
		}

		return $query;
	}

	/**
	 * @return null
	 */
	public function getCurrentLogId()
	{
		return $this->currentLogId;
	}

	/**
	 * @param null $currentLogId
	 */
	public function setCurrentLogId($currentLogId)
	{
		$this->currentLogId = $currentLogId;
	}

	public function createLog($data)
	{
		$log = $this->create($data);

		$this->setCurrentLogId($log->id);

		return $this->getCurrentLogId();
	}

	public function pageViews($minutes, $results)
	{
		 return $this->getModel()->pageViews($minutes, $results);
	}

	public function pageViewsByCountry($minutes, $results)
	{
		 return $this->getModel()->pageViewsByCountry($minutes, $results);
	}

	public function getErrors($minutes, $results)
	{
		return $this->getModel()->errors($minutes, $results);
	}

	public function allByRouteName($name, $minutes = null)
	{
		return $this->getModel()->allByRouteName($name, $minutes);
	}

	public function delete()
	{
		$this->getModel()->delete();
	}

}
