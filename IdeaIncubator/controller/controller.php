<?php
// Prevent PHP errors from breaking JSON responses
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Start output buffering to catch any unexpected output
ob_start();

// controller.php
session_start();
require_once("../model/model.php"); // contains our db logic

// If there's no command given, respond with an error or load a default page
if (!isset($_REQUEST['command'])) {
    // Possibly include("view/startpage.html"); or just do a message:
    echo json_encode(["status"=>"error","msg"=>"No command received"]);
    exit();
}

$command = $_REQUEST['command'];

// Discard any unexpected output that might have been generated
ob_clean();

try {
    switch ($command) {
        /* ====================================
                    USER COMMANDS
           ==================================== */
        case 'SignIn':
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            $user     = model_check_user($username, $password);
            if ($user) {
                // store session
                $_SESSION['user_id']   = $user['user_id'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['email']     = $user['email'];
                echo json_encode(["status"=>"ok","msg"=>"Sign in success"]);
            } else {
                echo json_encode(["status"=>"error","msg"=>"Invalid username or password"]);
            }
            break;

        case 'SignUp':
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            $email    = $_POST['email']    ?? '';
            
            // Basic validation
            if (empty($username) || empty($password)) {
                echo json_encode(["status"=>"error","msg"=>"Username and password are required"]);
                break;
            }
            
            $new_id = model_create_user($username, $password, $email);
            if ($new_id) {
                // auto-login
                $_SESSION['user_id']   = $new_id;
                $_SESSION['username']  = $username;
                $_SESSION['email']     = $email;
                echo json_encode(["status"=>"ok","msg"=>"User registered successfully"]);
            } else {
                echo json_encode(["status"=>"error","msg"=>"Registration failed. Username may already exist."]);
            }
            break;

        case 'SignOut':
            session_unset();
            session_destroy();
            echo json_encode(["status"=>"ok","msg"=>"You are signed out"]);
            break;

        case 'UpdateProfile':
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(["status"=>"error","msg"=>"Not signed in"]);
                break;
            }
            $newpass = $_POST['newpass'] ?? '';
            $newemail= $_POST['newemail'] ?? '';
            $done    = model_update_profile($_SESSION['user_id'], $newpass, $newemail);
            if ($done) {
                // update the session as well
                $_SESSION['email'] = $newemail;
                echo json_encode(["status"=>"ok","msg"=>"Profile updated"]);
            } else {
                echo json_encode(["status"=>"error","msg"=>"Profile update failed"]);
            }
            break;

        case 'Unsubscribe':
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(["status"=>"error","msg"=>"Not signed in"]);
                break;
            }
            $del = model_delete_user($_SESSION['user_id']);
            session_unset();
            session_destroy();
            if ($del) {
                echo json_encode(["status"=>"ok","msg"=>"Account deleted"]);
            } else {
                echo json_encode(["status"=>"error","msg"=>"Delete failed"]);
            }
            break;

        /* ====================================
                    IDEAS COMMANDS
           ==================================== */
        case 'PostIdea':
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(["status"=>"error","msg"=>"You must be signed in"]);
                break;
            }
            $title = $_POST['title'] ?? '';
            $description = $_POST['description'] ?? '';
            $cat = $_POST['category'] ?? '';
            $user_id = $_SESSION['user_id'];
            
            // Basic validation
            if (empty($title) || empty($description) || empty($cat)) {
                echo json_encode(["status"=>"error","msg"=>"Title, description and category are required"]);
                break;
            }
            
            $idea_id = model_create_idea($user_id, $title, $description, $cat);
            if ($idea_id) {
                echo json_encode(["status"=>"ok","msg"=>"Idea created","idea_id"=>$idea_id]);
            } else {
                echo json_encode(["status"=>"error","msg"=>"Failed to create idea"]);
            }
            break;

        case 'LoadIdeas':
            $ideas = model_get_all_ideas();
            echo json_encode($ideas);
            break;

        /* ====================================
                    COMMENTS COMMANDS
           ==================================== */
        case 'PostComment':
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(["status"=>"error","msg"=>"You must be signed in"]);
                break;
            }
            $idea_id = $_POST['idea_id'] ?? 0;
            $comment_text = $_POST['comment_text'] ?? '';
            $new_id = model_create_comment($_SESSION['user_id'], $idea_id, $comment_text);
            if ($new_id) {
                echo json_encode(["status"=>"ok","msg"=>"Comment posted"]);
            } else {
                echo json_encode(["status"=>"error","msg"=>"Failed to post comment"]);
            }
            break;

        case 'LoadComments':
            $idea_id = $_GET['idea_id'] ?? 0;
            $comments = model_get_comments_by_idea($idea_id);
            echo json_encode($comments);
            break;

        /* ====================================
                    VOTES COMMANDS
           ==================================== */
        case 'Vote':
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(["status"=>"error","msg"=>"You must be signed in"]);
                break;
            }
            $idea_id = $_POST['idea_id'] ?? 0;
            $vote_val= $_POST['vote_value'] ?? 1;
            $res     = model_vote($_SESSION['user_id'], $idea_id, $vote_val);
            if ($res) {
                // Optionally retrieve new total votes
                $score = model_get_vote_count($idea_id);
                echo json_encode(["status"=>"ok","msg"=>"Vote success","score"=>$score]);
            } else {
                echo json_encode(["status"=>"error","msg"=>"Vote failed"]);
            }
            break;

        case 'GetVoteCount':
            $idea_id = $_POST['idea_id'] ?? 0;
            if (!$idea_id) {
                echo json_encode(["status"=>"error","msg"=>"Invalid idea ID"]);
                break;
            }
            $score = model_get_vote_count($idea_id);
            echo json_encode(["status"=>"ok","score"=>$score]);
            break;

        case 'DeleteIdea':
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(["status"=>"error","msg"=>"Not logged in"]);
                break;
            }
            $idea_id = $_POST['idea_id'] ?? 0;
            $user_id = $_SESSION['user_id'];
            $done = model_delete_idea($idea_id, $user_id);
            if ($done) {
                echo json_encode(["status"=>"ok","msg"=>"Idea deleted"]);
            } else {
                echo json_encode(["status"=>"error","msg"=>"Unable to delete idea"]);
            }
            break;

        case 'EditIdea':
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(["status"=>"error","msg"=>"Not logged in"]);
                break;
            }
            $idea_id = $_POST['idea_id'] ?? 0;
            $new_title = $_POST['title'] ?? '';
            $new_desc = $_POST['description'] ?? '';
            $new_cat = $_POST['category'] ?? '';
            $user_id = $_SESSION['user_id'];
            
            $done = model_update_idea($idea_id, $user_id, $new_title, $new_desc, $new_cat);
            if ($done) {
                echo json_encode(["status"=>"ok","msg"=>"Idea updated"]);
            } else {
                echo json_encode(["status"=>"error","msg"=>"Failed to update idea"]);
            }
            break;

        case 'DBTest':
            // Test database connection and tables
            $conn = db_connect();
            if (!$conn) {
                echo json_encode(['status' => 'error', 'msg' => 'Failed to connect to database']);
                break;
            }
            
            // Check if tables exist
            $tables = [];
            $result = mysqli_query($conn, "SHOW TABLES");
            while ($row = mysqli_fetch_row($result)) {
                $tables[] = $row[0];
            }
            
            echo json_encode([
                'status' => 'ok', 
                'msg' => 'Database connection successful',
                'tables' => $tables
            ]);
            mysqli_close($conn);
            break;

        default:
            echo json_encode(["status"=>"error","msg"=>"Unknown command"]);
            break;
    }
} catch (Exception $e) {
    // Log error but don't expose details to the client
    error_log("Controller error: " . $e->getMessage());
    echo json_encode(["status"=>"error","msg"=>"An error occurred processing your request"]);
}

// End output buffering
ob_end_flush();
?>
