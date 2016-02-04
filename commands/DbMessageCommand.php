<?php

// todo dump
class DbMessageCommand extends CConsoleCommand
{
	public $sourcePath;
	public $fileTypes = ['php'];
	public $exclude = [
		'.svn',
		'.gitignore',
		'yiilite.php',
		'yiit.php',
		'/i18n/data',
		'/messages',
		'/vendors',
		'/web/js',
	];
	public $translator = 'Yii::t';
	public $removeOld = false;

	public function init()
	{
		echo Yii::t('vendor', 'Test');

		if (!isset($this->sourcePath)) {
			$this->sourcePath = Yii::getPathOfAlias('application');
		}

		if (!is_dir($this->sourcePath)) {
			$this->usageError("The source path $this->sourcePath is not a valid directory.");
		}

		if (!Yii::app()->messages instanceof CDbMessageSource) {
			$this->usageError("The messages component is not configured.");
		}

	}

	public function actionIndex()
	{

//		try {
		$options = [];
		if (isset($fileTypes))
			$options['fileTypes'] = $fileTypes;
		if (isset($exclude))
			$options['exclude'] = $exclude;
		$files = CFileHelper::findFiles(realpath($this->sourcePath), $options);

		$messages = [];
		foreach ($files as $file) {
			$messages = array_merge_recursive($messages, $this->extractMessages($file, $this->translator));
		}

		foreach ($messages as $category => $msgs) {
			$msgs = array_values(array_unique($msgs));

			$this->saveMessageSources($category, $msgs);
//			$this->generateMessageFile($msgs, $dir . DIRECTORY_SEPARATOR . $category . '.php', $overwrite, $removeOld, $sort, $fileHeader);
		}
//		} catch (Exception $e) {
//			echo $e->getMessage() . PHP_EOL;
//		}
	}

	// todo remove old
	protected function saveMessageSources($category, $messages)
	{
		/** @var CDbMessageSource $dbMessages */
		$dbMessages = Yii::app()->messages;
		$dbConnection = $dbMessages->dbConnection;

		foreach ($messages as $message) {

			$exist = $dbConnection->createCommand()
				->select('*')
				->from($dbMessages->sourceMessageTable)
				->where('category=:category AND message=:message', [':category' => $category, ':message' => $message])
				->queryRow();

			if ($exist === false) {
				$dbConnection->createCommand()
					->insert($dbMessages->sourceMessageTable, [
						'category' => $category,
						'message' => $message,
					]);
			}
		}

	}

	//todo preg dots
	protected function extractMessages($fileName, $translator)
	{
		echo "Extracting messages from $fileName...\n";
		$subject = file_get_contents($fileName);
		$messages = array();
		if (!is_array($translator))
			$translator = array($translator);

		foreach ($translator as $currentTranslator) {
			$n = preg_match_all('/\b' . $currentTranslator . '\s*\(\s*(\'[\w.\/]*?(?<!\.)\'|"[\w.]*?(?<!\.)")\s*,\s*(\'.*?(?<!\\\\)\'|".*?(?<!\\\\)")\s*[,\)]/s', $subject, $matches, PREG_SET_ORDER);

			for ($i = 0; $i < $n; ++$i) {
				if (($pos = strpos($matches[$i][1], '.')) !== false)
					$category = substr($matches[$i][1], $pos + 1, -1);
				else
					$category = substr($matches[$i][1], 1, -1);
				$message = $matches[$i][2];
				$messages[$category][] = eval("return $message;");  // use eval to eliminate quote escape
			}
		}
		return $messages;
	}
}