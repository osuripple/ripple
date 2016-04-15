<?php
class user extends Base {
	public static $table = 'users';
	public static function search($params = []) {
		$query = static ::where('rank', '>', '2');
		foreach ($params as $key => $value) {
			$query->where($key, '=', $value);
		}
		return $query->fetch();
	}
	public static function paginate($page = 1, $perpage = 10) {
		$query = Query::table(static ::table());
		$count = $query->count();
		$results = $query->take($perpage)->skip(($page - 1) * $perpage)->sort('id', 'asc')->get();
		return new Paginator($results, $count, $page, $perpage, Uri::to('admin/users'));
	}
	public static function table($name = null) {
		return 'users';
	}
}
