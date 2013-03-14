<?php
class SearchableShell extends AppShell {
    public $tasks = array('Searchable.Index'); 
    public function main() {
        $this->Index->execute();
    }
}