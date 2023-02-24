<?php

namespace Netflex\Database\Driver;

final class Command
{
    const SEARCH = 'search';
    const STRUCTURE_EXISTS = 'structure_exists';
    const CREATE_STRUCTURE = 'create_structure';
    const CREATE_STRUCTURE_FIELD = 'create_structure_field';
    const DELETE_STRUCTURE = 'delete_structure';
    const DELETE_STRUCTURE_IF_EXISTS = 'delete_structure_if_exists';
    const DELETE_STRUCTURE_FIELD = 'delete_structure_field';
    const DELETE_STRUCTURE_FIELD_IF_EXISTS = 'delete_structure_field_if_exists';
    const RENAME_STRUCTURE_FIELD = 'rename_structure_field';
    const LIST_FIELDS = 'list_fields';
}
