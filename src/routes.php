<?php

// Function to define CRUD routes for each table in the database
function defineRoutesForTables($router, $database, $sqlFile)
{
    // Read the SQL file
    $sqlContent = file_get_contents($sqlFile);

    // Extract table names from SQL content
    preg_match_all('/CREATE TABLE `(.+?)`/', $sqlContent, $matches);

    // Define routes for each table
    foreach ($matches[1] as $tableName) {
        // Define CRUD routes for the table
        defineResourceRoutes($router, $database, $tableName);
    }
}


// Define CRUD routes for a resource
function defineResourceRoutes($router, $database, $resourceName)
{
    $router->get("/$resourceName", function () use ($database, $resourceName) {
        $items = $database->getAll($resourceName);
        echo json_encode($items);
    });

    $router->get("/$resourceName/{id}", function ($id) use ($database, $resourceName) {
        $item = $database->getById($resourceName, (int) $id);
        if ($item) {
            echo json_encode($item);
        } else {
            http_response_code(404);
            echo json_encode(['error' => ucfirst($resourceName) . ' not found']);
        }
    });

    $router->post("/$resourceName", function () use ($database, $resourceName) {
        $requestData = json_decode(file_get_contents('php://input'), true);
        if (!empty($requestData)) {
            if ($database->insert($resourceName, $requestData)) {
                http_response_code(201);
                echo json_encode(['message' => ucfirst($resourceName) . ' created successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create ' . $resourceName]);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid request body']);
        }
    });

    $router->put("/$resourceName/{id}", function ($id) use ($database, $resourceName) {
        $requestData = json_decode(file_get_contents('php://input'), true);
        if (!empty($requestData)) {
            if ($database->update($resourceName, (int) $id, $requestData)) {
                echo json_encode(['message' => ucfirst($resourceName) . ' updated successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update ' . $resourceName]);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid request body']);
        }
    });

    $router->delete("/$resourceName/{id}", function ($id) use ($database, $resourceName) {
        if ($database->delete($resourceName, (int) $id)) {
            echo json_encode(['message' => ucfirst($resourceName) . ' deleted successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete ' . $resourceName]);
        }
    });
}
