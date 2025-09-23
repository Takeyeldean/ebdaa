<?php
/**
 * URL Router for إبداع Website
 * Handles clean URLs and routes them to appropriate PHP files
 */

// Start session for all routes (only if not already started)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get the requested URL path
$request_uri = $_SERVER['REQUEST_URI'];
$script_name = $_SERVER['SCRIPT_NAME'];
$path_info = parse_url($request_uri, PHP_URL_PATH);

// Remove the base path if the script is in a subdirectory
$base_path = dirname($script_name);
if ($base_path !== '/') {
    $path_info = substr($path_info, strlen($base_path));
}

// Remove leading and trailing slashes
$path_info = trim($path_info, '/');

// Split the path into segments
$segments = $path_info ? explode('/', $path_info) : [];

// Define routes
$routes = [
    // Public routes
    '' => 'index.php',
    'login' => 'index.php',
    'logout' => 'logout.php',
    
    // Admin routes
    'admin' => 'admin.php',
    'admin/groups' => 'admin.php',
    'admin/questions' => 'admin_questions.php',
    'admin/invitations' => 'admin_invitations.php',
    'admin/group/{id}' => 'manage_group.php',
    'admin/group/{id}/manage' => 'manage_group.php',
    'admin/group/{id}/students' => 'manage_group.php',
    'admin/group/{id}/invite' => 'invite_admin.php',
    'admin/group/{id}/leave' => 'leave_group.php',
    'admin/group/{id}/message' => 'update_group_message.php',
    'admin/group/{id}/student/{student_id}/move' => 'move_student.php',
    'admin/group/{id}/student/{student_id}/delete' => 'delete_student.php',
    'admin/group/{id}/student/{student_id}/rename' => 'change_student_name.php',
    'admin/add-group' => 'add_group.php',
    'admin/add-student' => 'add.php',
    
    // Student routes
    'dashboard' => 'dashboard.php',
    'dashboard/group/{id}' => 'dashboard.php',
    'questions' => 'student_questions.php',
    'profile' => 'profile.php',
    'profile/update' => 'update_info.php',
    'profile/image' => 'upload_image.php',
    'profile/degree' => 'update_degree.php',
    
    // API routes (if needed)
    'api/student/degree' => 'update_degree.php',
    'api/student/info' => 'update_info.php',
    'api/student/image' => 'upload_image.php',
];

// Function to match routes with parameters
function matchRoute($segments, $routes) {
    foreach ($routes as $route => $file) {
        $route_segments = explode('/', $route);
        
        // Skip if segment count doesn't match
        if (count($segments) !== count($route_segments)) {
            continue;
        }
        
        $params = [];
        $match = true;
        
        for ($i = 0; $i < count($segments); $i++) {
            $route_segment = $route_segments[$i];
            $url_segment = $segments[$i];
            
            // Check if this is a parameter (wrapped in {})
            if (preg_match('/^{(.+)}$/', $route_segment, $matches)) {
                $param_name = $matches[1];
                $params[$param_name] = $url_segment;
            } else {
                // Direct match required
                if ($route_segment !== $url_segment) {
                    $match = false;
                    break;
                }
            }
        }
        
        if ($match) {
            return ['file' => $file, 'params' => $params];
        }
    }
    
    return null;
}

// Try to match the route
$route_match = matchRoute($segments, $routes);

if ($route_match) {
    $target_file = $route_match['file'];
    $params = $route_match['params'];
    
    // Set parameters as GET variables
    foreach ($params as $key => $value) {
        $_GET[$key] = $value;
    }
    
    // Check if the target file exists
    if (file_exists($target_file)) {
        // Include the target file
        include $target_file;
    } else {
        // File not found
        http_response_code(404);
        include '404.php';
    }
} else {
    // No route matched - 404
    http_response_code(404);
    include '404.php';
}
?>
