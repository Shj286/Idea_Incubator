<?php
// model.php
require_once(__DIR__ . "/db.php");

/* ----------------------------
   USER MANAGEMENT
-------------------------------*/

// Check if username/password is valid
function model_check_user($username, $password) {
    $conn = db_connect();
    if (!$conn) return null;
    
    // Simple example: plain text password
    $sql = "SELECT * FROM Users 
            WHERE username='$username' 
            AND password='$password'
            LIMIT 1";
    $res = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($res);
    mysqli_close($conn);
    return $row; // either the user row or null
}

// Create new user (SignUp)
function model_create_user($username, $password, $email) {
    $conn = db_connect();
    if (!$conn) return false;
    
    // Insert user directly - using correct fields
    $sql = "INSERT INTO Users (username, password, email)
            VALUES ('$username', '$password', '$email')";
    $res = mysqli_query($conn, $sql);
    if ($res) {
        $new_id = mysqli_insert_id($conn);
        mysqli_close($conn);
        return $new_id;
    } else {
        mysqli_close($conn);
        return false;
    }
}

// Update user profile (change password/email, for example)
function model_update_profile($user_id, $new_password, $new_email) {
    $conn = db_connect();
    if (!$conn) return false;
    
    $sql = "UPDATE Users
            SET password='$new_password', email='$new_email'
            WHERE user_id=$user_id";
    $res = mysqli_query($conn, $sql);
    $affected = mysqli_affected_rows($conn);
    mysqli_close($conn);
    return ($res && $affected > 0);
}

// Delete user (Unsubscribe)
function model_delete_user($user_id) {
    $conn = db_connect();
    if (!$conn) return false;
    
    $sql = "DELETE FROM Users WHERE user_id=$user_id";
    $res = mysqli_query($conn, $sql);
    mysqli_close($conn);
    return $res;
}

/* ----------------------------
   IDEAS
-------------------------------*/
function model_create_idea($user_id, $title, $content, $category) {
    $conn = db_connect();
    if (!$conn) return false;
    
    // Simple sanitization - replace single quotes with double quotes to avoid SQL issues
    $title = str_replace("'", "\"", $title);
    $content = str_replace("'", "\"", $content);
    $category = str_replace("'", "\"", $category);
    
    // Use the correct field names for Ideas table
    $sql = "INSERT INTO Ideas (user_id, title, description, category)
            VALUES ($user_id, '$title', '$content', '$category')";
    
    $res = mysqli_query($conn, $sql);
    $id = $res ? mysqli_insert_id($conn) : false;
    mysqli_close($conn);
    return $id;
}

// List all ideas in descending order
function model_get_all_ideas() {
    $conn = db_connect();
    if (!$conn) return array();
    
    // Fix: Use 'created_date' instead of 'created_at' and 'id' instead of 'idea_id'
    $sql = "SELECT i.*, u.username
            FROM Ideas i
            JOIN Users u ON i.user_id = u.user_id
            ORDER BY i.created_date DESC";
    $res = mysqli_query($conn, $sql);

    $ideas = array();
    while ($row = mysqli_fetch_assoc($res)) {
        $ideas[] = $row;
    }
    mysqli_close($conn);
    return $ideas;
}

/* ----------------------------
   COMMENTS
-------------------------------*/
function model_create_comment($user_id, $idea_id, $content) {
    $conn = db_connect();
    if (!$conn) return false;
    
    // Simple sanitization to avoid SQL issues
    $content = str_replace("'", "\"", $content);
    
    // Use 'comment_text' instead of 'content'
    $sql = "INSERT INTO Comments (idea_id, user_id, comment_text)
            VALUES ($idea_id, $user_id, '$content')";
    
    $res = mysqli_query($conn, $sql);
    $new_id = $res ? mysqli_insert_id($conn) : false;
    mysqli_close($conn);
    return $new_id;
}

function model_get_comments_by_idea($idea_id) {
    $conn = db_connect();
    if (!$conn) return array();
    
    error_log("Loading comments for idea_id: $idea_id");
    
    // Fix: Use 'id' not 'comment_id', 'comment_date' not 'created_at'
    $sql = "SELECT c.*, u.username
            FROM Comments c
            JOIN Users u ON c.user_id = u.user_id
            WHERE c.idea_id=$idea_id
            ORDER BY c.comment_date ASC";
    error_log("Comments SQL: $sql");
    
    $res = mysqli_query($conn, $sql);
    if (!$res) {
        error_log("Error loading comments: " . mysqli_error($conn));
        mysqli_close($conn);
        return array();
    }

    $comments = array();
    while ($row = mysqli_fetch_assoc($res)) {
        $comments[] = $row;
    }
    error_log("Loaded " . count($comments) . " comments");
    mysqli_close($conn);
    return $comments;
}

/* ----------------------------
   VOTES
-------------------------------*/
function model_vote($user_id, $idea_id, $vote_value) {
    $conn = db_connect();
    if (!$conn) return false;
    
    error_log("Processing vote - user_id: $user_id, idea_id: $idea_id, value: $vote_value");
    
    // Fix: Use 'id' not 'vote_id'
    $check = "SELECT id FROM Votes 
              WHERE user_id=$user_id AND idea_id=$idea_id";
    error_log("Vote check SQL: $check");
    
    $cres = mysqli_query($conn, $check);
    if (!$cres) {
        error_log("Error checking for existing vote: " . mysqli_error($conn));
        mysqli_close($conn);
        return false;
    }
    
    if (mysqli_num_rows($cres) > 0) {
        // Update existing vote
        $row = mysqli_fetch_assoc($cres);
        $vid = $row['id'];
        // Fix: Table doesn't have vote_value in your schema
        $sql = "UPDATE Votes SET vote_date=NOW() WHERE id=$vid";
        error_log("Updating vote - SQL: $sql");
        $res = mysqli_query($conn, $sql);
    } else {
        // Insert new vote
        // Fix: Table doesn't have vote_value
        $sql = "INSERT INTO Votes (user_id, idea_id, vote_date)
                VALUES ($user_id, $idea_id, NOW())";
        error_log("Creating vote - SQL: $sql");
        $res = mysqli_query($conn, $sql);
    }
    
    if (!$res) {
        error_log("Vote operation failed: " . mysqli_error($conn));
    } else {
        error_log("Vote operation successful");
    }
    
    mysqli_close($conn);
    return $res;
}

// For convenience, get total upvotes for an idea
function model_get_vote_count($idea_id) {
    $conn = db_connect();
    if (!$conn) return 0;
    
    // Fix: Just count votes, since there's no vote_value field
    $sql = "SELECT COUNT(*) AS score FROM Votes WHERE idea_id=$idea_id";
    $res = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($res);
    mysqli_close($conn);
    return (int)($row['score'] ?? 0);
}

function model_update_idea($idea_id, $user_id, $new_title, $new_content, $new_category) {
    $conn = db_connect();
    if (!$conn) return false;
    
    // Simple sanitization
    $new_title = str_replace("'", "\"", $new_title);
    $new_content = str_replace("'", "\"", $new_content);
    $new_category = str_replace("'", "\"", $new_category);
    
    // Use 'id' not 'idea_id', 'description' not 'content'
    $sql = "UPDATE Ideas
            SET title='$new_title', description='$new_content', category='$new_category'
            WHERE id=$idea_id AND user_id=$user_id";
    $res = mysqli_query($conn, $sql);
    $affected = mysqli_affected_rows($conn);
    mysqli_close($conn);
    return ($res && $affected > 0); 
}

function model_delete_idea($idea_id, $user_id) {
    $conn = db_connect();
    if (!$conn) return false;
    
    // Fix: Use 'id' not 'idea_id'
    $sql = "DELETE FROM Ideas WHERE id=$idea_id AND user_id=$user_id";
    $res = mysqli_query($conn, $sql);
    $affected = mysqli_affected_rows($conn);
    mysqli_close($conn);
    return ($res && $affected > 0);
}

?>
