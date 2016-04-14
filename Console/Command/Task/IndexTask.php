<?php
class IndexTask extends Shell {
    public $uses = array('Searchable.SearchIndex');
    public $tasks = array('DbConfig', 'Model', 'Searchable.ProgressBar');
    public $modelCache = array();

    protected function execute() {
        if (isset($this->args[0])) {
            $this->connection = $this->args[0];
            $this->out(__('Using database config: %s', $this->connection));
        }

        if (empty($this->connection)) {
            $this->connection = $this->DbConfig->getConfig();
        }

        $this->tables = $this->Model->getAllTables($this->connection);
        $this->modelNames = array();

        if (isset($this->args[1])) {
            $models = str_replace(' ', '', $this->args[1]);

            if (strtolower($models) == 'all') {
                $count = count($this->tables);
                $models = array();
                for ($i = 0; $i < $count; $i++) {
                    $models[] = $this->_modelName($this->tables[$i]);
                }
            } else {
                $models = explode(',', $models);
            }

            foreach ($models as $k => $model) {
                if (strtolower($model) != 'searchindex') {
                    App::uses($model, 'Model');
                    if (class_exists($model)) {
                        $tmpModel = new $model();
                        $behaviors = $tmpModel->Behaviors->loaded();
                        if ( in_array('Searchable',$behaviors) ) {
                             $this->modelCache[$model] = $tmpModel;
                             $this->modelNames[$k] = $model;
                        } else {
                            $this->out(__('<warning>Notice: Searchable is no behaviour for %s.</warning>', $model));
                        }
                    } else {
                        $this->error(__('Class %s does not exists.', $model));
                    }
                }
            }
            
            if (count($this->modelCache) > 0) {
                $this->out(__('Rebuilding indices for: %s', implode(', ', array_keys($this->modelCache))));
                $this->hr();
                $this->indexModels();
                $this->finish();
            }

            return;
        }

        $this->hr();
        $this->out(__('Rebuild index for the following models:'));
        $this->hr();
        $count = count($this->tables);
        $this->out(sprintf("%2d. %s", 0, __('All models')));
        $tableIndex = 0;
        for ($i = 0; $i < $count; $i++) {
            $model = $this->_modelName($this->tables[$i]);
            if (strtolower($model) != 'searchindex') {
                App::uses($model, 'Model');
                if (class_exists($model)) {
                    $tmpModel = new $model();
                    if (isset($tmpModel->actsAs) && is_array($tmpModel->actsAs) && isset($tmpModel->actsAs['Searchable.Searchable'])) {
                        $this->modelCache[$model] = $tmpModel;
                        $tableIndex++;
                        $this->modelNames[$tableIndex] = $model;
                        $this->out(sprintf("%2d. %s", $tableIndex, $this->modelNames[$tableIndex]));
                        unset($tmpModel);
                    }
                }
            }
        }
        $this->out(sprintf(" Q. %s", __('Quit')));
        $modelChoice = strtolower($this->in(null, null, 'Q'));

        if ($modelChoice == 'q') {
            $this->out('');
            $this->hr();
            return;
        }

        if ($modelChoice == '0') {
            $this->indexModels();
        } else {
            $this->indexModel($this->modelNames[(int)$modelChoice]);
        }

        $this->finish();
        return;
    }

    protected function indexModel($model) {
        $results = $this->modelCache[$model]->find('all', array(
            'recursive' => 0
        ));
        $this->out(__('Creating new indices for %s...', $model));
        $this->ProgressBar->start(count($results));

        foreach ($results as $result) {
            $this->modelCache[$model]->id = $result[$model]['id'];
            $data = $result[$model];
            $data['modified'] = false;
            unset($data['id']);
            $this->modelCache[$model]->save($data);
            $this->ProgressBar->next();
        }

        $this->out('');
        $this->hr();
    }

    protected function indexModels() {
        foreach ($this->modelNames as $model) {
            $this->indexModel($model);
        }
    }

    protected function finish() {
        $this->out('', 1);
        $this->out(__('All indices have been updated :)'), 2);
        $this->hr();
    }
}
