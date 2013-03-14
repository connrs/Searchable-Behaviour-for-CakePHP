This is my Github fork of the [Searchable Behaviour](http://code.google.com/p/searchable-behaviour-for-cakephp/) project on Google Code. The project holds an [MIT Licence](http://www.opensource.org/licenses/mit-license.php) and credit/copyright goes to Calin for such a great effort.

This behaviour allows Cake developers to make a model **fulltext searchable**, meaning that the user will be able to perform mysql fulltext search (which powers Wikimedia for example) on the models. 

For example, a fulltext search allows end users to use boolean expressions (eg. '-' to exclude a term) or natural language search which interprets the search string as a phrase in natural human language. 

## Setup

To setup this behaviour you will need to create the following MySQL table 

    CREATE TABLE `search_indices` (
    	`id` int(11) NOT NULL auto_increment,
    	`association_key` varchar(36) NOT NULL,
    	`model` varchar(128) collate utf8_unicode_ci NOT NULL,
    	`data` longtext collate utf8_unicode_ci NOT NULL,
    	`created` datetime NOT NULL,
    	`modified` datetime NOT NULL,
    	PRIMARY KEY  (`id`),
    	KEY `association_key` (`association_key`,`model`),
    	FULLTEXT KEY `data` (`data`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

### Model setup

To create indices for model data, you have to add the Searchable behavior to the model.

#### Create index all model fields

    <?php
    class Post extends AppModel {
        var $name = 'Post';
        var $actsAs = array('Searchable.Searchable');
    }
    ?>

#### Create index for only for headline and text fields:

    <?php
    class Post extends AppModel {
        var $name = 'Post';
        var $actsAs = array(
            'Searchable.Searchable' => array(
                'fields' => array(
                    'headline',
                    'text',
                )
            )
        );
    }
    ?>

#### Stopwords:

Stopwords can be found in the config/stopwords.php file

    <?php
    class Post extends AppModel {
        var $name = 'Post';
        var $actsAs = array(
            'Searchable.Searchable' => array(
                'stopwords_lang' => 'german'
            )
        );
    }
    ?>

## Usage

At your disposal, there are several ways to use it: 

### SearchIndex Model

You can use the SearchIndex model to do the searches using the regular cake php ** Model->find() **. By default this will search all the subjects (if you have more than one searchable model). To search only ceratin models you can use **SearchIndex->searchModels()** 

An example usage, using cakephp pagination: 

    <?php
    	$this->SearchIndex->searchModels(array('Post','Comment'));
    	$this->paginate = array(
    		'limit' => 10,
    		'conditions' =>  "MATCH(SearchIndex.data) AGAINST('$q' IN BOOLEAN MODE)"
    	);
    	$this->set('results', $this->paginate('SearchIndex'));
    ?>

### The searchable model

To do a search on the actual searchable model, you can use **Model->search(query)** 

### Indexing data

To provide a custom indexing function, you can define your own indexData() function. By default all varchar, char and text fields are indexed. 

    <?php
    class Comment extends AppModel {
    
    	var $name = 'Comment';
    	var $actsAs = array(
            'Searchable.Searchable' => array(
                'fields' => array('text')
            )
        );
    	
    	function indexData() {
    		$index = $this->data['Comment']['text'];
    		return $index;
    	}
    }
    ?>

### Rebuild index from shell

To rebuild the index from shell, you can run the wizard

    app/Console/cake Searchable.Searchable index

Or you can speficy for which modely you want to rebuild the index

    app/Console/cake Searchable.Searchable index <database config> <model name>

Rebuild index for all models with the searchable behaviour

    app/Console/cake Searchable.Searchable index default all

Rebuild index for post and comments models

    app/Console/cake Searchable.Searchable index default Post,Comment

Rebuild index only for comments models

    app/Console/cake Searchable.Searchable index default Comment


 [1]: #Setup
 [2]: #Usage
 [3]: #Model
 [4]: #The_searchable_model
 [5]: #Indexing_data
 [6]: #Rebuild_index_from_shell

