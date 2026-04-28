<?php

// Mocking required classes for isolation
class Hebergement {
    private $id;
    private $titre;
    private $prix;
    private $reservations = [];

    public function __construct($id, $titre, $prix) {
        $this->id = $id;
        $this->titre = $titre;
        $this->prix = $prix;
    }
    public function getId_hebergement() { return $this->id; }
    public function getTitre() { return $this->titre; }
    public function getPrixParNuit() { return $this->prix; }
    public function getReservations() { return $this->reservations; }
    public function addReservation($r) { $this->reservations[] = $r; }
}

class Reservation {
    private $start;
    private $end;
    private $status;
    public function __construct($start, $end, $status = 'CONFIRMEE') {
        $this->start = new \DateTime($start);
        $this->end = new \DateTime($end);
        $this->status = $status;
    }
    public function getDateDebutR() { return $this->start; }
    public function getDateFinR() { return $this->end; }
    public function getStatutR() { return $this->status; }
}

// Logic extracted from DynamicPricingService for testing
function calculateOccupancy($hebergement) {
    $now = new \DateTime('today'); // Fixed code
    $endPeriod = (clone $now)->modify('+30 days');
    $occupiedDays = 0;

    echo "Now: " . $now->format('Y-m-d H:i:s') . "\n";

    foreach ($hebergement->getReservations() as $res) {
        if ($res->getStatutR() === 'CONFIRMEE') {
            $start = $res->getDateDebutR();
            $end = $res->getDateFinR();

            if ($start < $endPeriod && $end > $now) {
                $actualStart = max($start, $now);
                $actualEnd = min($end, $endPeriod);
                $diff = $actualStart->diff($actualEnd);
                $occupiedDays += $diff->days;
                echo "Res: " . $start->format('Y-m-d') . " to " . $end->format('Y-m-d') . " -> Actual: " . $actualStart->format('Y-m-d H:i:s') . " to " . $actualEnd->format('Y-m-d H:i:s') . " -> Days: " . $diff->days . "\n";
            }
        }
    }
    return $occupiedDays;
}

// TEST 1: Reservation starting today
$h = new Hebergement(1, "Villa Tunis", 100);
$h->addReservation(new Reservation('today', 'tomorrow')); // 1 night starting today
echo "Test 1 (1 night starting today):\n";
$days = calculateOccupancy($h);
echo "Occupied Days: $days\n\n";

// TEST 2: Reservation spanning 3 days
$h2 = new Hebergement(2, "Villa Sousse", 100);
// Reservation from yesterday to 2 days after tomorrow (4 nights total)
$h2->addReservation(new Reservation('yesterday', 'today + 3 days')); 
echo "Test 2 (4 nights, spanning today):\n";
$days2 = calculateOccupancy($h2);
echo "Occupied Days: $days2\n\n";
