<?php

namespace App\Controllers;

use mysqli;

class AdvisorStudentController
{
    public function getAdvisees($advisor_id)
    {
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "course_mark_management";

        $conn = new mysqli($servername, $username, $password, $dbname);

        if ($conn->connect_error) {
            die("connection failed: " . $conn->connect_error);
        }

        $sql = "
          SELECT u.username AS student_name, as_rel.matric_number, as_rel.gpa, as_rel.semester, as_rel.year, as_rel.risk
          FROM advisor_student AS as_rel
          JOIN user u ON as_rel.student_id = u.user_id
          WHERE as_rel.advisor_id = $advisor_id;
        ";

        $result = $conn->query($sql);

        $students = [];
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $students[] = $row;
            }
        }

        $conn->close();

        echo json_encode($students);
    }
}
