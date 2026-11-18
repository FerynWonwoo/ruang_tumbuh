
<?php

class User
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // Mengambil data pengguna berdasarkan username
    public function login($username)
    {
        $query = "SELECT id, nama, username, password, role
                  FROM users
                  WHERE username = ?
                  LIMIT 1";

        $stmt = mysqli_prepare($this->conn, $query);

        if (!$stmt) {
            error_log(mysqli_error($this->conn));
            return false;
        }

        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);

        return $user;
    }

    // Memperbarui password
    public function updatePassword($id, $password)
    {
        $query = "UPDATE users SET password = ? WHERE id = ?";

        $stmt = mysqli_prepare($this->conn, $query);

        if (!$stmt) {
            error_log(mysqli_error($this->conn));
            return false;
        }

        mysqli_stmt_bind_param($stmt, "si", $password, $id);
        $result = mysqli_stmt_execute($stmt);

        mysqli_stmt_close($stmt);

        return $result;
    }

    public function register($nama, $username, $email, $password)
    {
        // Cek username atau email
        $query = "SELECT id FROM users
                  WHERE username = ? OR email = ?
                  LIMIT 1";

        $stmt = mysqli_prepare($this->conn, $query);

        if (!$stmt) {
            throw new Exception(mysqli_error($this->conn));
        }

        mysqli_stmt_bind_param($stmt, "ss", $username, $email);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $existingUser = mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);

        if ($existingUser) {
            return "duplicate";
        }

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Simpan pengguna baru
        $query = "INSERT INTO users
                  (nama, username, email, password, role)
                  VALUES (?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($this->conn, $query);

        if (!$stmt) {
            throw new Exception(mysqli_error($this->conn));
        }

        $role = "user";

        mysqli_stmt_bind_param(
            $stmt,
            "sssss",
            $nama,
            $username,
            $email,
            $hashedPassword,
            $role
        );

        $success = mysqli_stmt_execute($stmt);

        mysqli_stmt_close($stmt);

        return $success;
    }

}
