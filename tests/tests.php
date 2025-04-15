<?php

require_once __DIR__ . '/testframework.php';

// Update paths for Docker environment
if (file_exists('/var/www/html/config.php')) {
    // We're in the container
    require_once '/var/www/html/config.php';
    require_once '/var/www/html/modules/database.php';
    require_once '/var/www/html/modules/page.php';
} else {
    // We're in local development
    require_once __DIR__ . '/../site/config.php';
    require_once __DIR__ . '/../site/modules/database.php';
    require_once __DIR__ . '/../site/modules/page.php';
}

$tests = new TestFramework();

// test 1: check database connection
function testDbConnection() {
    global $config;
    
    try {
        $db = new Database($config["db"]["path"]);
        return assertExpression(true, "Database connection successful", "Failed to connect to database");
    } catch (Exception $e) {
        error("Exception: " . $e->getMessage());
        return false;
    }
}

// test 2: test count method
function testDbCount() {
    global $config;
    
    try {
        $db = new Database($config["db"]["path"]);
        $count = $db->Count("page");
        return assertExpression($count >= 3, "Count method returned $count pages", "Count method failed: expected at least 3 pages, got $count");
    } catch (Exception $e) {
        error("Exception: " . $e->getMessage());
        return false;
    }
}

// test 3: test create method
function testDbCreate() {
    global $config;
    
    try {
        $db = new Database($config["db"]["path"]);
        $data = [
            'title' => 'Test Page',
            'content' => 'Test Content'
        ];
        $id = $db->Create("page", $data);
        return assertExpression($id > 0, "Create method returned ID: $id", "Create method failed");
    } catch (Exception $e) {
        error("Exception: " . $e->getMessage());
        return false;
    }
}

// test 4: test read method
function testDbRead() {
    global $config;
    
    try {
        $db = new Database($config["db"]["path"]);
        $id = 1; // Read the first page
        $data = $db->Read("page", $id);
        return assertExpression(
            $data && isset($data['title']) && isset($data['content']),
            "Read method returned data for page ID: $id - Title: {$data['title']}",
            "Read method failed for page ID: $id"
        );
    } catch (Exception $e) {
        error("Exception: " . $e->getMessage());
        return false;
    }
}

// test 5: test update method
function testDbUpdate() {
    global $config;
    
    try {
        $db = new Database($config["db"]["path"]);
        $id = 1;
        $newData = [
            'title' => 'Updated Title',
            'content' => 'Updated Content'
        ];
        $result = $db->Update("page", $id, $newData);
        
        // Check if update was successful by reading the updated record
        $updated = $db->Read("page", $id);
        return assertExpression(
            $updated && $updated['title'] == $newData['title'] && $updated['content'] == $newData['content'],
            "Update method successfully updated page ID: $id",
            "Update method failed for page ID: $id"
        );
    } catch (Exception $e) {
        error("Exception: " . $e->getMessage());
        return false;
    }
}

// test 6: test fetch method
function testDbFetch() {
    global $config;
    
    try {
        $db = new Database($config["db"]["path"]);
        $rows = $db->Fetch("SELECT * FROM page");
        return assertExpression(
            is_array($rows) && count($rows) > 0,
            "Fetch method returned " . count($rows) . " rows",
            "Fetch method failed"
        );
    } catch (Exception $e) {
        error("Exception: " . $e->getMessage());
        return false;
    }
}

// test 7: test delete method
function testDbDelete() {
    global $config;
    
    try {
        $db = new Database($config["db"]["path"]);
        
        // Create a record to delete
        $data = [
            'title' => 'Delete Test',
            'content' => 'To Be Deleted'
        ];
        $id = $db->Create("page", $data);
        
        // Delete the record
        $result = $db->Delete("page", $id);
        
        // Try to read the deleted record
        $deleted = $db->Read("page", $id);
        
        return assertExpression(
            $deleted === false || $deleted === null || empty($deleted),
            "Delete method successfully deleted page ID: $id",
            "Delete method failed for page ID: $id"
        );
    } catch (Exception $e) {
        error("Exception: " . $e->getMessage());
        return false;
    }
}

// test 8: test Page class
function testPageRender() {
    try {
        // Use the correct template path based on environment
        $templatePath = file_exists('/var/www/html/templates/index.tpl') ? 
            '/var/www/html/templates/index.tpl' : 
            __DIR__ . '/../site/templates/index.tpl';
        
        $page = new Page($templatePath);
        $data = [
            'title' => 'Test Title',
            'subtitle' => 'Test Subtitle',
            'content' => 'Test Content',
            'footer' => 'Test Footer'
        ];
        
        $content = $page->Render($data);
        
        return assertExpression(
            strpos($content, 'Test Title') !== false && 
            strpos($content, 'Test Subtitle') !== false && 
            strpos($content, 'Test Content') !== false && 
            strpos($content, 'Test Footer') !== false,
            "Page render method successfully rendered the template",
            "Page render method failed to render the template"
        );
    } catch (Exception $e) {
        error("Exception: " . $e->getMessage());
        return false;
    }
}

// add tests
$tests->add('Database connection', 'testDbConnection');
$tests->add('Table count', 'testDbCount');
$tests->add('Data create', 'testDbCreate');
$tests->add('Data read', 'testDbRead');
$tests->add('Data update', 'testDbUpdate');
$tests->add('Data fetch', 'testDbFetch');
$tests->add('Data delete', 'testDbDelete');
$tests->add('Page render', 'testPageRender');

// run tests
$tests->run();

echo $tests->getResult(); 