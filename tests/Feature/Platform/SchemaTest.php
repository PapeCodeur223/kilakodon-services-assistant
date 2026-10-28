<?php

use Illuminate\Support\Facades\DB;

it('n\'a aucune colonne réécrite automatiquement à chaque mise à jour (ON UPDATE CURRENT_TIMESTAMP)', function () {
    // MariaDB ajoute ce comportement à la première colonne TIMESTAMP NOT NULL d'une table :
    // une date d'expiration ou de calcul serait alors écrasée silencieusement.
    $columns = DB::select(
        'select table_name as t, column_name as c from information_schema.columns where table_schema = ? and lower(extra) like ?',
        [DB::getDatabaseName(), '%on update%'],
    );

    expect(array_map(fn ($row) => $row->t.'.'.$row->c, $columns))->toBe([]);
});
