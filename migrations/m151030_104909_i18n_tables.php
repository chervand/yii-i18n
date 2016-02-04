<?php

class m151030_104909_i18n_tables extends CDbMigration
{
	private $_options = 'engine=innodb default charset=utf8 collate=utf8_unicode_ci';

	public function safeUp()
	{
		$this->createTable('i18n_source_message', [
			'id' => 'pk',
			'category' => 'varchar(32)',
			'message' => 'text'
		], $this->_options);

		$this->createTable('i18n_translated_message', [
			'id' => 'integer',
			'language' => 'varchar(16)',
			'translation' => 'text',
			'primary key (id, language)',
			'foreign key (id) references i18n_source_message (id) on delete cascade on update restrict'
		], $this->_options);
	}

	public function safeDown()
	{
		$this->dropTable('i18n_message');
		$this->dropTable('i18n_source_message');
	}
}