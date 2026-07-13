public static function importFromStorage()
{
    Excel::import(new static, 'Quiz template.xlsx', 'local');
}
