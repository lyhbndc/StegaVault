<?php
session_start();

$supabaseUrl = "https://dknxptrhnjpcymvvmdpj.supabase.co";
$apikey = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImRrbnhwdHJobmpwY3ltdnZtZHBqIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzU5MDgzMzcsImV4cCI6MjA5MTQ4NDMzN30.GpxxJYjheOY3HZ66b32PLF8b35lSvKRc8Vmy6_7zvxc"; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];

    $data = [
        "email" => $email,
        "password" => $password
    ];

    $ch = curl_init("$supabaseUrl/auth/v1/token?grant_type=password");

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "apikey: $apikey",
        "Authorization: Bearer $apikey"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);
    curl_close($ch);

    $responseData = json_decode($response, true);

    // ✅ Check if login successful
    if (isset($responseData['access_token'])) {
        $_SESSION['user'] = $responseData['user']['email'];
        $_SESSION['token'] = $responseData['access_token'];

        header("Location: dashboard.php");
        exit();
    } else {
        $error = $responseData['error_description'] ?? 'Login failed';
    }
}
?>

<form method="POST">
    <input type="email" name="email" placeholder="Email" required>
    <br>
    <input type="password" name="password" placeholder="Password" required>
    <br>
    <button type="submit">Login</button>
</form>

<?php
if (isset($error)) {
    echo "<p style='color:red;'>$error</p>";
}
?>
