<?php

namespace Abbasudo\Purity\Filters\Strategies;

use Abbasudo\Purity\Filters\Filter;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ContainsFilter extends Filter
{
    /**
     * Operator string to detect in the query params.
     *
     * @var string
     */
    protected static string $operator = '$contains';

    /**
     * Apply filter logic to $query.
     *
     * @return Closure
     */
    public function apply(): Closure
    {
        return function ($query) {
            $connection = DB::connection()->getDriverName();

            $column = str($this->column)
                ->explode('.')
                ->map(fn ($v) => "`{$v}`")
                ->join('.');

            foreach ($this->values as $value) {
                switch ($connection) {
                    case 'sqlite':
                    case 'mariadb':
                    case 'mysql':
                        $query->whereRaw("{$column} LIKE ?", ["%{$value}%"]);
                        break;
                    case 'pgsql':
                        $query->where($this->column, 'ILIKE', "%{$value}%");
                        break;
                    case 'sqlsrv':
                        $query->whereRaw("{$column} COLLATE Latin1_General_CI_AS LIKE ?", ["%{$value}%"]);
                        break;
                    default:
                        throw new RuntimeException("Unsupported database driver: {$connection}");
                }
            }
        };
    }
}
