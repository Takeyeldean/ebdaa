<?php
/**
 * URL Helper Functions
 * Provides functions to generate clean URLs
 */

/**
 * Generate a clean URL for the given route
 * 
 * @param string $route The route name
 * @param array $params Optional parameters for the route
 * @return string The clean URL
 */
function url($route, $params = []) {
    $base_url = '/ebdaa';
    
    // Define route mappings
    $routes = [
        'home' => '',
        'login' => 'login',
        'logout' => 'logout',
        'admin' => 'admin',
        'admin.groups' => 'admin/groups',
        'admin.questions' => 'admin/questions',
        'admin.invitations' => 'admin/invitations',
        'admin.group' => 'admin/group/{id}',
        'admin.group.manage' => 'admin/group/{id}/manage',
        'admin.group.students' => 'admin/group/{id}/students',
        'admin.group.invite' => 'admin/group/{id}/invite',
        'admin.group.leave' => 'admin/group/{id}/leave',
        'admin.group.message' => 'admin/group/{id}/message',
        'admin.group.student.move' => 'admin/group/{id}/student/{student_id}/move',
        'admin.group.student.delete' => 'admin/group/{id}/student/{student_id}/delete',
        'admin.group.student.rename' => 'admin/group/{id}/student/{student_id}/rename',
        'admin.add-group' => 'admin/add-group',
        'admin.add-student' => 'admin/add-student',
        'dashboard' => 'dashboard',
        'dashboard.group' => 'dashboard/group/{id}',
        'questions' => 'questions',
        'profile' => 'profile',
        'profile.update' => 'profile/update',
        'profile.image' => 'profile/image',
        'profile.degree' => 'profile/degree',
    ];
    
    if (!isset($routes[$route])) {
        return '#';
    }
    
    $url = $routes[$route];
    
    // Replace parameters in the URL
    foreach ($params as $key => $value) {
        $url = str_replace('{' . $key . '}', $value, $url);
    }
    
    return $base_url . '/' . $url;
}

/**
 * Generate a URL for admin group management
 * 
 * @param int $group_id The group ID
 * @return string The clean URL
 */
function adminGroupUrl($group_id) {
    return url('admin.group', ['id' => $group_id]);
}

/**
 * Generate a URL for dashboard with group
 * 
 * @param int $group_id The group ID
 * @return string The clean URL
 */
function dashboardGroupUrl($group_id) {
    return url('dashboard.group', ['id' => $group_id]);
}

/**
 * Generate a URL for student management
 * 
 * @param int $group_id The group ID
 * @param int $student_id The student ID
 * @param string $action The action (move, delete, rename)
 * @return string The clean URL
 */
function studentActionUrl($group_id, $student_id, $action) {
    return url('admin.group.student.' . $action, [
        'id' => $group_id,
        'student_id' => $student_id
    ]);
}

/**
 * Check if current URL matches the given route
 * 
 * @param string $route The route to check
 * @return bool True if current URL matches the route
 */
function isRoute($route) {
    $current_path = trim($_SERVER['REQUEST_URI'], '/');
    $route_path = trim(url($route), '/');
    
    return $current_path === $route_path;
}

/**
 * Get the current route name
 * 
 * @return string The current route name
 */
function currentRoute() {
    $path = trim($_SERVER['REQUEST_URI'], '/');
    $segments = $path ? explode('/', $path) : [];
    
    // Simple route detection based on URL structure
    if (empty($segments)) {
        return 'home';
    }
    
    $first_segment = $segments[0];
    
    switch ($first_segment) {
        case 'admin':
            if (isset($segments[1])) {
                switch ($segments[1]) {
                    case 'groups':
                        return 'admin.groups';
                    case 'questions':
                        return 'admin.questions';
                    case 'invitations':
                        return 'admin.invitations';
                    case 'add-group':
                        return 'admin.add-group';
                    case 'add-student':
                        return 'admin.add-student';
                    case 'group':
                        if (isset($segments[3])) {
                            switch ($segments[3]) {
                                case 'manage':
                                case 'students':
                                    return 'admin.group.manage';
                                case 'invite':
                                    return 'admin.group.invite';
                                case 'leave':
                                    return 'admin.group.leave';
                                case 'message':
                                    return 'admin.group.message';
                                case 'student':
                                    if (isset($segments[5])) {
                                        return 'admin.group.student.' . $segments[5];
                                    }
                                    break;
                            }
                        }
                        return 'admin.group';
                }
            }
            return 'admin';
            
        case 'dashboard':
            if (isset($segments[1]) && $segments[1] === 'group') {
                return 'dashboard.group';
            }
            return 'dashboard';
            
        case 'questions':
            return 'questions';
            
        case 'profile':
            if (isset($segments[1])) {
                switch ($segments[1]) {
                    case 'update':
                        return 'profile.update';
                    case 'image':
                        return 'profile.image';
                    case 'degree':
                        return 'profile.degree';
                }
            }
            return 'profile';
            
        case 'login':
            return 'login';
            
        default:
            return 'home';
    }
}
?>
