<?php
// api/reports.php
// Generowanie raportów PDF:
// 1) Raport całościowy dla wszystkich budów
// 2) Raport szczegółowy dla każdej pojedynczej budowy
//
// Wymagana biblioteka FPDF (np. fpdf.php w katalogu lib/).

if (!defined('APP_INIT')) define('APP_INIT', true);

header('Content-Type: application/json'); // Domyślnie JSON dla błędów
ini_set('display_errors', 0);

require_once __DIR__ . '/../config/db.php';

// Ścieżka do FPDF – dopasuj do swojego katalogu
require_once __DIR__ . '/../lib/fpdf.php';

/**
 * Pomocnicza funkcja pobierająca pełną strukturę danych:
 * projekty -> zespoły -> ludzie, zadania
 */
function getFullStructure(PDO $pdo): array {
    $data = [
        'projects' => [],
    ];

    // Projekty
    $stmt = $pdo->query("SELECT id, name FROM projects ORDER BY name ASC");
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$projects) {
        return $data;
    }

    // Zespoły dla wszystkich projektów
    $projectIds = array_column($projects, 'id');
    $in = implode(',', array_fill(0, count($projectIds), '?'));

    $stmtTeams = $pdo->prepare("SELECT id, project_id, team_name FROM teams WHERE project_id IN ($in) ORDER BY team_name ASC");
    $stmtTeams->execute($projectIds);
    $teams = $stmtTeams->fetchAll(PDO::FETCH_ASSOC);

    // Członkowie zespołów
    $teamById = [];
    foreach ($teams as $t) {
        $t['members'] = [];
        $t['tasks']   = [];
        $teamById[$t['id']] = $t;
    }

    if ($teamById) {
        $teamIds = array_keys($teamById);
        $inTeams = implode(',', array_fill(0, count($teamIds), '?'));

        // Pracownicy
        $stmtMembers = $pdo->prepare("
            SELECT tm.team_id, e.full_name
            FROM team_members tm
            JOIN employees e ON e.id = tm.employee_id
            WHERE tm.team_id IN ($inTeams)
            ORDER BY e.full_name ASC
        ");
        $stmtMembers->execute($teamIds);
        while ($row = $stmtMembers->fetch(PDO::FETCH_ASSOC)) {
            $teamById[$row['team_id']]['members'][] = $row['full_name'];
        }

        // Zadania
        $stmtTasks = $pdo->prepare("
            SELECT t.id, t.title, t.assigned_to_team_id, t.project_id
            FROM tasks t
            WHERE t.assigned_to_team_id IN ($inTeams)
            ORDER BY t.id ASC
        ");
        $stmtTasks->execute($teamIds);
        while ($row = $stmtTasks->fetch(PDO::FETCH_ASSOC)) {
            $tid = (int)$row['assigned_to_team_id'];
            if (isset($teamById[$tid])) {
                $teamById[$tid]['tasks'][] = $row['title'];
            }
        }
    }

    // Sklej wszystko per projekt
    foreach ($projects as $p) {
        $pid = (int)$p['id'];
        $pTeams = [];
        foreach ($teamById as $t) {
            if ((int)$t['project_id'] === $pid) {
                $pTeams[] = $t;
            }
        }
        $data['projects'][] = [
            'id'    => $pid,
            'name'  => $p['name'],
            'teams' => $pTeams,
        ];
    }

    return $data;
}

/**
 * Raport całościowy – wszystkie budowy
 */
function generateOverallReport(PDO $pdo): FPDF {
    $structure = getFullStructure($pdo);
    $today     = date('Y-m-d');

    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->SetTitle('Raport całościowy – obsada i zadania');
    $pdf->SetAuthor('PLAN BRYGAD');

    $pdf->AddPage();
    $pdf->SetMargins(15, 15, 15);

    // Nagłówek
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, iconv('UTF-8', 'ISO-8859-2', 'RAPORT CAŁOŚCIOWY – OBSADA I ZADANIA'), 0, 1, 'C');
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(0, 8, $today, 0, 1, 'C');
    $pdf->Ln(4);

    $pdf->SetFont('Arial', '', 11);

    foreach ($structure['projects'] as $project) {
        // Nazwa budowy
        $pdf->SetFont('Arial', 'B', 13);
        $pdf->Ln(4);
        $pdf->Cell(0, 7, iconv('UTF-8', 'ISO-8859-2', $project['name']), 0, 1);
        $pdf->SetFont('Arial', '', 11);

        if (empty($project['teams'])) {
            $pdf->Cell(0, 6, iconv('UTF-8', 'ISO-8859-2', 'Brak zdefiniowanych zespołów.'), 0, 1);
            continue;
        }

        foreach ($project['teams'] as $team) {
            $teamName = 'ZESPÓŁ ' . ($team['team_name'] ?? '');
            $members  = $team['members'] ?? [];
            $tasks    = $team['tasks'] ?? [];

            $membersText = $members ? implode(', ', $members) : '-';
            $tasksText   = $tasks ? implode(' | ', $tasks) : '-';

            // Zespół
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(0, 6, iconv('UTF-8', 'ISO-8859-2', $teamName), 0, 1);

            // Ludzie
            $pdf->SetFont('Arial', '', 11);
            $pdf->MultiCell(
                0,
                5,
                iconv('UTF-8', 'ISO-8859-2', 'Ludzie: ' . $membersText),
                0,
                'L'
            );

            // Zadania
            $pdf->MultiCell(
                0,
                5,
                iconv('UTF-8', 'ISO-8859-2', 'Zadania: ' . $tasksText),
                0,
                'L'
            );

            $pdf->Ln(2);
        }

        $pdf->Ln(2);
    }

    return $pdf;
}

/**
 * Raport szczegółowy dla jednej budowy – z kolumną do odhaczania zadań.
 */
function generateProjectReport(PDO $pdo, int $projectId): ?FPDF {
    $structure = getFullStructure($pdo);
    $today     = date('Y-m-d');

    $projectData = null;
    foreach ($structure['projects'] as $p) {
        if ($p['id'] === $projectId) {
            $projectData = $p;
            break;
        }
    }

    if (!$projectData) {
        return null;
    }

    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->SetTitle('Raport budowy – ' . $projectData['name']);
    $pdf->SetAuthor('PLAN BRYGAD');

    $pdf->AddPage();
    $pdf->SetMargins(15, 15, 15);

    // Nagłówek
    $pdf->SetFont('Arial', 'B', 15);
    $title = sprintf('BUDOWA: %s — %s', $projectData['name'], $today);
    $pdf->MultiCell(0, 8, iconv('UTF-8', 'ISO-8859-2', $title), 0, 'L');
    $pdf->Ln(4);

    $pdf->SetFont('Arial', '', 11);

    // Szerokości kolumn: lewa (zadania) + prawa (weryfikacja)
    $leftWidth  = 130;
    $rightWidth = 50;

    foreach ($projectData['teams'] as $team) {
        $teamName = 'ZESPÓŁ ' . ($team['team_name'] ?? '');
        $members  = $team['members'] ?? [];
        $tasks    = $team['tasks'] ?? [];

        $membersText = $members ? implode(', ', $members) : '-';

        // Nagłówek zespołu
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 7, iconv('UTF-8', 'ISO-8859-2', $teamName), 0, 1);

        // Skład
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(
            0,
            6,
            iconv('UTF-8', 'ISO-8859-2', 'Skład: ' . $membersText),
            0,
            1
        );

        // Zadania w dwóch kolumnach: lewa – treść zadania, prawa – weryfikacja
        if (!$tasks) {
            // Pusta linia, gdy brak zadań
            $yStart = $pdf->GetY();
            $pdf->SetFont('Arial', 'I', 10);
            $pdf->MultiCell(
                $leftWidth,
                5,
                iconv('UTF-8', 'ISO-8859-2', 'Brak przypisanych zadań'),
                1,
                'L'
            );
            $yEnd = $pdf->GetY();
            $rowHeight = $yEnd - $yStart;

            // Prawa kolumna – puste pole weryfikacji
            $pdf->SetXY($pdf->GetX() + $leftWidth, $yStart);
            $pdf->Cell($rightWidth, $rowHeight, '', 1, 1);
        } else {
            foreach ($tasks as $task) {
                $yStart = $pdf->GetY();

                // Lewa kolumna – treść zadania (wielolinijkowa)
                $pdf->SetFont('Arial', '', 11);
                $pdf->MultiCell(
                    $leftWidth,
                    5,
                    iconv('UTF-8', 'ISO-8859-2', $task),
                    1,
                    'L'
                );
                $yEnd = $pdf->GetY();
                $rowHeight = $yEnd - $yStart;

                // Prawa kolumna – puste pole do odhaczania / podpisu
                $pdf->SetXY(15 + $leftWidth, $yStart);
                $pdf->Cell($rightWidth, $rowHeight, '', 1, 1);

                // Ustaw nowy Y pod całym wierszem
                $pdf->SetY($yEnd);
            }
        }

        $pdf->Ln(4);
    }

    return $pdf;
}

// --- Kontroler wejścia HTTP ---

// Parametry:
// mode = all        -> generuje i wysyła PDF całościowy
// mode = project&id -> generuje i wysyła PDF dla jednej budowy

$mode = $_GET['mode'] ?? 'all';

try {
    if ($mode === 'all') {
        $pdf = generateOverallReport($pdo);

        // Wyślij bezpośrednio jako plik do pobrania
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="raport_calosc.pdf"');
        $pdf->Output('I', 'raport_calosc.pdf');
        exit;
    } elseif ($mode === 'project') {
        $projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
        if (!$projectId) {
            throw new Exception('Brak parametru project_id');
        }

        $pdf = generateProjectReport($pdo, $projectId);
        if (!$pdf) {
            throw new Exception('Nie znaleziono budowy o podanym ID');
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="raport_budowa_' . $projectId . '.pdf"');
        $pdf->Output('I', 'raport_budowa_' . $projectId . '.pdf');
        exit;
    } else {
        throw new Exception('Nieznany tryb generowania raportu');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

