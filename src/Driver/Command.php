<?php

namespace Netflex\Database\Driver;

final class Command
{
    const SELECT = 'select';
    const INSERT = 'insert';
    const UPDATE = 'update';
    const DELETE = 'delete';

    const TABLE_EXISTS = 'tableExists';
    const TABLE_CREATE = 'createTable';
    const TABLE_DROP = 'dropTable';
    const TABLE_DROP_IF_EXISTS = 'dropTableIfExists';

    const TABLE_COLUMNS_SELECT = 'selectColumns';

    const TABLE_COLUMN_EXISTS = 'columnExists';
    const TABLE_COLUMN_ADD = 'addColumn';
    const TABLE_COLUMN_ALTER = 'alterColumn';
    const TABLE_COLUMN_DROP = 'dropColumn';
    const TABLE_COLUMN_DROP_IF_EXISTS = 'dropColumnIfExists';
}
