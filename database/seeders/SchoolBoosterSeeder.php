<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SchoolCategory;
use App\Models\SchoolBusiness;
use App\Models\SchoolItem;
use App\Models\SchoolPackage;
use App\Models\SchoolTierItem;
use Illuminate\Support\Facades\DB;

class SchoolBoosterSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->seedPrimarySchools();
            $this->seedSecondarySchools();
            $this->seedECDSchools();
            $this->seedAdminOffice();
        });
    }

    // ─── CATEGORIES ──────────────────────────────────────────────────────────

    private function category(string $name, string $emoji): SchoolCategory
    {
        return SchoolCategory::firstOrCreate(['name' => $name], ['emoji' => $emoji]);
    }

    private function business(SchoolCategory $cat, string $name, string $desc, string $img): SchoolBusiness
    {
        return SchoolBusiness::firstOrCreate(
            ['school_category_id' => $cat->id, 'name' => $name],
            ['description' => $desc, 'image_url' => $img]
        );
    }

    private function item(SchoolBusiness $biz, array $data): SchoolItem
    {
        return SchoolItem::firstOrCreate(
            ['item_code' => $data['item_code']],
            array_merge($data, ['school_business_id' => $biz->id, 'is_active' => true])
        );
    }

    private function package(SchoolBusiness $biz, string $tier, string $name, string $desc, float $price, float $deposit, int $months, array $lineItems): void
    {
        if (SchoolPackage::where('school_business_id', $biz->id)->where('tier', $tier)->exists()) {
            return;
        }

        $monthly = round(($price - $deposit) * (1 + 1.08) / $months, 2);

        $pkg = SchoolPackage::create([
            'school_business_id' => $biz->id,
            'tier'               => $tier,
            'name'               => $name,
            'description'        => $desc,
            'price'              => $price,
            'deposit'            => $deposit,
            'monthly_installment'=> $monthly,
            'loan_term'          => $months,
            'interest_rate'      => 108.00,
            'is_active'          => true,
        ]);

        foreach ($lineItems as [$item, $qty]) {
            SchoolTierItem::firstOrCreate(
                ['school_package_id' => $pkg->id, 'school_item_id' => $item->id],
                ['quantity' => $qty]
            );
        }
    }

    // ─── PRIMARY SCHOOLS ─────────────────────────────────────────────────────

    private function seedPrimarySchools(): void
    {
        $cat = $this->category('Primary Schools', '🏫');

        // ── Classroom Furniture ───────────────────────────────────────────────
        $biz = $this->business(
            $cat,
            'Classroom Furniture & Equipment',
            'Equip primary school classrooms with durable desks, chairs, boards and storage.',
            'https://images.unsplash.com/photo-1580582932707-520aed937b7b?auto=format&fit=crop&w=800&q=80'
        );

        $desk     = $this->item($biz, ['item_code' => 'SCH-CF-001', 'name' => 'Student Desk & Chair Set',   'unit' => 'set',  'unit_cost' => 45.00,  'markup_percentage' => 20]);
        $tDesk    = $this->item($biz, ['item_code' => 'SCH-CF-002', 'name' => "Teacher's Desk",             'unit' => 'each', 'unit_cost' => 85.00,  'markup_percentage' => 20]);
        $tChair   = $this->item($biz, ['item_code' => 'SCH-CF-003', 'name' => "Teacher's Chair",            'unit' => 'each', 'unit_cost' => 40.00,  'markup_percentage' => 20]);
        $board    = $this->item($biz, ['item_code' => 'SCH-CF-004', 'name' => 'Chalkboard (1.2m × 2.4m)',   'unit' => 'each', 'unit_cost' => 120.00, 'markup_percentage' => 20]);
        $cabinet  = $this->item($biz, ['item_code' => 'SCH-CF-005', 'name' => 'Storage Cabinet',            'unit' => 'each', 'unit_cost' => 95.00,  'markup_percentage' => 20]);
        $curtains = $this->item($biz, ['item_code' => 'SCH-CF-006', 'name' => 'Classroom Curtain Set',      'unit' => 'set',  'unit_cost' => 55.00,  'markup_percentage' => 20]);
        $noticeBd = $this->item($biz, ['item_code' => 'SCH-CF-007', 'name' => 'Notice Board (corkboard)',   'unit' => 'each', 'unit_cost' => 45.00,  'markup_percentage' => 20]);
        $wboard   = $this->item($biz, ['item_code' => 'SCH-CF-008', 'name' => 'Whiteboard with Markers Kit','unit' => 'each', 'unit_cost' => 95.00,  'markup_percentage' => 20]);

        $this->package($biz, 'essential', 'Single Classroom Starter', '1 fully furnished classroom for up to 10 pupils.', 950.00, 190.00, 12, [
            [$desk, 10], [$tDesk, 1], [$tChair, 1], [$board, 1], [$noticeBd, 1],
        ]);
        $this->package($biz, 'intermediate', '3-Classroom Setup', '3 classrooms fully furnished for up to 30 pupils each.', 3200.00, 640.00, 18, [
            [$desk, 30], [$tDesk, 3], [$tChair, 3], [$board, 3], [$cabinet, 3], [$noticeBd, 3],
        ]);
        $this->package($biz, 'advanced', '6-Classroom Block', '6 classrooms with whiteboards, curtains and storage.', 6800.00, 1360.00, 24, [
            [$desk, 60], [$tDesk, 6], [$tChair, 6], [$wboard, 6], [$cabinet, 6], [$curtains, 6], [$noticeBd, 6],
        ]);
        $this->package($biz, 'premium', 'Full School Furniture Package', 'Complete furniture for 10 classrooms plus assembly hall seating.', 13500.00, 2700.00, 24, [
            [$desk, 100], [$tDesk, 10], [$tChair, 10], [$wboard, 10], [$cabinet, 10], [$curtains, 10], [$noticeBd, 10],
        ]);

        // ── Sports & PE ───────────────────────────────────────────────────────
        $biz2 = $this->business(
            $cat,
            'Sports & Physical Education',
            'Kits, nets, balls and field markings for primary school PE programmes.',
            'https://images.unsplash.com/photo-1543326727-cf6c39e8f84c?auto=format&fit=crop&w=800&q=80'
        );

        $football   = $this->item($biz2, ['item_code' => 'SCH-SP-001', 'name' => 'Football (Size 4)',              'unit' => 'each', 'unit_cost' => 22.00,  'markup_percentage' => 20]);
        $netball    = $this->item($biz2, ['item_code' => 'SCH-SP-002', 'name' => 'Netball Post Set',               'unit' => 'set',  'unit_cost' => 185.00, 'markup_percentage' => 20]);
        $volleyball = $this->item($biz2, ['item_code' => 'SCH-SP-003', 'name' => 'Volleyball Net & Ball Set',      'unit' => 'set',  'unit_cost' => 75.00,  'markup_percentage' => 20]);
        $whistle    = $this->item($biz2, ['item_code' => 'SCH-SP-004', 'name' => 'Referee Whistle Set (6 pack)',   'unit' => 'set',  'unit_cost' => 18.00,  'markup_percentage' => 20]);
        $firstAid   = $this->item($biz2, ['item_code' => 'SCH-SP-005', 'name' => 'Sports First Aid Kit',           'unit' => 'kit',  'unit_cost' => 45.00,  'markup_percentage' => 20]);
        $jerseys    = $this->item($biz2, ['item_code' => 'SCH-SP-006', 'name' => 'Sports Jerseys (set of 20)',     'unit' => 'set',  'unit_cost' => 120.00, 'markup_percentage' => 20]);
        $hurdles    = $this->item($biz2, ['item_code' => 'SCH-SP-007', 'name' => 'Athletics Hurdles Set (6 pcs)', 'unit' => 'set',  'unit_cost' => 95.00,  'markup_percentage' => 20]);
        $skipRopes  = $this->item($biz2, ['item_code' => 'SCH-SP-008', 'name' => 'Skipping Ropes (pack of 20)',   'unit' => 'pack', 'unit_cost' => 35.00,  'markup_percentage' => 20]);
        $cones      = $this->item($biz2, ['item_code' => 'SCH-SP-009', 'name' => 'Training Cones (pack of 50)',   'unit' => 'pack', 'unit_cost' => 28.00,  'markup_percentage' => 20]);
        $goalpost   = $this->item($biz2, ['item_code' => 'SCH-SP-010', 'name' => 'Football Goalpost Set',          'unit' => 'set',  'unit_cost' => 320.00, 'markup_percentage' => 20]);

        $this->package($biz2, 'essential', 'Basic Games Kit', 'Ball sports starter kit for everyday PE lessons.', 650.00, 130.00, 12, [
            [$football, 4], [$whistle, 1], [$skipRopes, 2], [$cones, 1], [$firstAid, 1],
        ]);
        $this->package($biz2, 'intermediate', 'Multi-Sport Kit', 'Football, netball and volleyball for structured inter-class competitions.', 1800.00, 360.00, 18, [
            [$football, 6], [$netball, 1], [$volleyball, 1], [$jerseys, 1], [$whistle, 1], [$skipRopes, 2], [$cones, 1], [$firstAid, 1],
        ]);
        $this->package($biz2, 'advanced', 'Full PE Programme', 'Complete programme with athletics, team sports and safety equipment.', 3800.00, 760.00, 24, [
            [$football, 10], [$netball, 2], [$volleyball, 1], [$hurdles, 1], [$jerseys, 2], [$goalpost, 1], [$whistle, 2], [$cones, 1], [$firstAid, 2],
        ]);
        $this->package($biz2, 'premium', 'Sports Grounds Development', 'Football pitch with goalposts, athletics track markings kit and full team gear.', 8500.00, 1700.00, 24, [
            [$football, 20], [$netball, 2], [$volleyball, 2], [$hurdles, 2], [$jerseys, 4], [$goalpost, 2], [$whistle, 4], [$cones, 2], [$skipRopes, 4], [$firstAid, 3],
        ]);
    }

    // ─── SECONDARY SCHOOLS ───────────────────────────────────────────────────

    private function seedSecondarySchools(): void
    {
        $cat = $this->category('Secondary Schools', '🎓');

        // ── Science Laboratory ────────────────────────────────────────────────
        $biz = $this->business(
            $cat,
            'Science Laboratory',
            'Equip your school laboratory with benches, microscopes, glassware and chemicals.',
            'https://images.unsplash.com/photo-1530026405186-ed1f139313f8?auto=format&fit=crop&w=800&q=80'
        );

        $labBench   = $this->item($biz, ['item_code' => 'SCH-LAB-001', 'name' => 'Laboratory Bench (2-seat)',        'unit' => 'each', 'unit_cost' => 380.00, 'markup_percentage' => 20]);
        $microscope = $this->item($biz, ['item_code' => 'SCH-LAB-002', 'name' => 'Compound Microscope',             'unit' => 'each', 'unit_cost' => 220.00, 'markup_percentage' => 20]);
        $bunsen     = $this->item($biz, ['item_code' => 'SCH-LAB-003', 'name' => 'Bunsen Burner',                   'unit' => 'each', 'unit_cost' => 35.00,  'markup_percentage' => 20]);
        $glassware  = $this->item($biz, ['item_code' => 'SCH-LAB-004', 'name' => 'Glassware Starter Set',           'unit' => 'set',  'unit_cost' => 190.00, 'markup_percentage' => 20]);
        $goggles    = $this->item($biz, ['item_code' => 'SCH-LAB-005', 'name' => 'Safety Goggles (pack of 30)',     'unit' => 'pack', 'unit_cost' => 65.00,  'markup_percentage' => 20]);
        $labCoats   = $this->item($biz, ['item_code' => 'SCH-LAB-006', 'name' => 'Lab Coats (pack of 30)',          'unit' => 'pack', 'unit_cost' => 180.00, 'markup_percentage' => 20]);
        $chemicals  = $this->item($biz, ['item_code' => 'SCH-LAB-007', 'name' => 'Chemistry Chemicals Starter Kit', 'unit' => 'kit',  'unit_cost' => 280.00, 'markup_percentage' => 20]);
        $scale      = $this->item($biz, ['item_code' => 'SCH-LAB-008', 'name' => 'Digital Weighing Scale',          'unit' => 'each', 'unit_cost' => 95.00,  'markup_percentage' => 20]);
        $extgr      = $this->item($biz, ['item_code' => 'SCH-LAB-009', 'name' => 'Fire Extinguisher (lab-grade)',   'unit' => 'each', 'unit_cost' => 65.00,  'markup_percentage' => 20]);
        $eyeWash    = $this->item($biz, ['item_code' => 'SCH-LAB-010', 'name' => 'Emergency Eye Wash Station',      'unit' => 'each', 'unit_cost' => 120.00, 'markup_percentage' => 20]);
        $dissect    = $this->item($biz, ['item_code' => 'SCH-LAB-011', 'name' => 'Dissection Kit (set of 10)',      'unit' => 'set',  'unit_cost' => 85.00,  'markup_percentage' => 20]);
        $periodic   = $this->item($biz, ['item_code' => 'SCH-LAB-012', 'name' => 'Periodic Table Wall Chart',       'unit' => 'each', 'unit_cost' => 20.00,  'markup_percentage' => 20]);

        $this->package($biz, 'essential', 'Basic Science Lab Starter', 'Core equipment for basic Biology and Chemistry practicals.', 2500.00, 500.00, 18, [
            [$labBench, 5], [$bunsen, 5], [$glassware, 1], [$goggles, 1], [$labCoats, 1], [$scale, 1], [$extgr, 1], [$periodic, 1],
        ]);
        $this->package($biz, 'intermediate', 'Full Chemistry & Biology Lab', 'Complete lab with microscopes, chemicals and safety equipment for 30 students.', 6500.00, 1300.00, 24, [
            [$labBench, 10], [$microscope, 10], [$bunsen, 10], [$glassware, 2], [$goggles, 1], [$labCoats, 1], [$chemicals, 1], [$scale, 2], [$extgr, 2], [$eyeWash, 1], [$periodic, 2],
        ]);
        $this->package($biz, 'advanced', 'Science Block — 2 Labs', 'Two fully-equipped labs (Chemistry + Biology) for up to 60 students per session.', 14000.00, 2800.00, 24, [
            [$labBench, 20], [$microscope, 20], [$bunsen, 20], [$glassware, 4], [$goggles, 2], [$labCoats, 2], [$chemicals, 2], [$scale, 4], [$extgr, 4], [$eyeWash, 2], [$dissect, 2], [$periodic, 4],
        ]);
        $this->package($biz, 'premium', 'Triple Science Complex', 'Biology, Chemistry and Physics labs — full equipment, safety systems and demo benches.', 28000.00, 5600.00, 24, [
            [$labBench, 30], [$microscope, 30], [$bunsen, 30], [$glassware, 6], [$goggles, 3], [$labCoats, 3], [$chemicals, 4], [$scale, 6], [$extgr, 6], [$eyeWash, 3], [$dissect, 4], [$periodic, 6],
        ]);

        // ── ICT Computer Lab ─────────────────────────────────────────────────
        $biz2 = $this->business(
            $cat,
            'ICT & Computer Laboratory',
            'Set up a modern computer lab with desktops, networking and projection equipment.',
            'https://images.unsplash.com/photo-1588072432836-e10032774350?auto=format&fit=crop&w=800&q=80'
        );

        $pc         = $this->item($biz2, ['item_code' => 'SCH-ICT-001', 'name' => 'Desktop Computer (Core i5, 8GB RAM)', 'unit' => 'each', 'unit_cost' => 480.00, 'markup_percentage' => 20]);
        $monitor    = $this->item($biz2, ['item_code' => 'SCH-ICT-002', 'name' => 'LED Monitor 21"',                    'unit' => 'each', 'unit_cost' => 180.00, 'markup_percentage' => 20]);
        $compDesk   = $this->item($biz2, ['item_code' => 'SCH-ICT-003', 'name' => 'Computer Desk (single)',              'unit' => 'each', 'unit_cost' => 75.00,  'markup_percentage' => 20]);
        $compChair  = $this->item($biz2, ['item_code' => 'SCH-ICT-004', 'name' => 'Computer Chair',                     'unit' => 'each', 'unit_cost' => 45.00,  'markup_percentage' => 20]);
        $switch     = $this->item($biz2, ['item_code' => 'SCH-ICT-005', 'name' => 'Network Switch (24-port)',            'unit' => 'each', 'unit_cost' => 95.00,  'markup_percentage' => 20]);
        $cabling    = $this->item($biz2, ['item_code' => 'SCH-ICT-006', 'name' => 'Network Cabling Kit',                 'unit' => 'kit',  'unit_cost' => 120.00, 'markup_percentage' => 20]);
        $projector  = $this->item($biz2, ['item_code' => 'SCH-ICT-007', 'name' => 'LCD Projector (3000 lumens)',         'unit' => 'each', 'unit_cost' => 380.00, 'markup_percentage' => 20]);
        $screen     = $this->item($biz2, ['item_code' => 'SCH-ICT-008', 'name' => 'Projector Screen (2m × 2m)',          'unit' => 'each', 'unit_cost' => 95.00,  'markup_percentage' => 20]);
        $ups        = $this->item($biz2, ['item_code' => 'SCH-ICT-009', 'name' => 'UPS Unit (1500VA)',                   'unit' => 'each', 'unit_cost' => 155.00, 'markup_percentage' => 20]);
        $printer    = $this->item($biz2, ['item_code' => 'SCH-ICT-010', 'name' => 'Laser Printer',                       'unit' => 'each', 'unit_cost' => 240.00, 'markup_percentage' => 20]);
        $surge      = $this->item($biz2, ['item_code' => 'SCH-ICT-011', 'name' => 'Surge Protector Strip (8-way)',       'unit' => 'each', 'unit_cost' => 28.00,  'markup_percentage' => 20]);

        $this->package($biz2, 'essential', 'Starter Computer Lab (10 PCs)', '10-workstation lab with networking and projection.', 9500.00, 1900.00, 24, [
            [$pc, 10], [$monitor, 10], [$compDesk, 10], [$compChair, 10], [$switch, 1], [$cabling, 1], [$projector, 1], [$screen, 1], [$ups, 5], [$surge, 10],
        ]);
        $this->package($biz2, 'intermediate', '20-Workstation Lab', 'Full class of 20 workstations with teacher station and printer.', 18500.00, 3700.00, 24, [
            [$pc, 21], [$monitor, 21], [$compDesk, 21], [$compChair, 21], [$switch, 1], [$cabling, 2], [$projector, 1], [$screen, 1], [$ups, 10], [$printer, 1], [$surge, 21],
        ]);
        $this->package($biz2, 'advanced', '30-Workstation Lab + Server', '30 workstations, server, interactive whiteboard and dual projectors.', 32000.00, 6400.00, 24, [
            [$pc, 31], [$monitor, 31], [$compDesk, 31], [$compChair, 31], [$switch, 2], [$cabling, 3], [$projector, 2], [$screen, 2], [$ups, 15], [$printer, 2], [$surge, 31],
        ]);
        $this->package($biz2, 'premium', 'Dual Lab ICT Complex', 'Two 30-seat labs with full networking, server room, interactive display and technical support kit.', 65000.00, 13000.00, 24, [
            [$pc, 62], [$monitor, 62], [$compDesk, 62], [$compChair, 62], [$switch, 4], [$cabling, 6], [$projector, 4], [$screen, 4], [$ups, 30], [$printer, 4], [$surge, 62],
        ]);

        // ── Library & Resources ───────────────────────────────────────────────
        $biz3 = $this->business(
            $cat,
            'Library & Learning Resources',
            'Build a well-stocked school library with shelves, reading furniture and curriculum books.',
            'https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?auto=format&fit=crop&w=800&q=80'
        );

        $bookshelf  = $this->item($biz3, ['item_code' => 'SCH-LIB-001', 'name' => 'Bookshelf (6-tier, double-sided)',   'unit' => 'each', 'unit_cost' => 110.00, 'markup_percentage' => 20]);
        $rdgTable   = $this->item($biz3, ['item_code' => 'SCH-LIB-002', 'name' => 'Reading Table (seats 6)',            'unit' => 'each', 'unit_cost' => 130.00, 'markup_percentage' => 20]);
        $libChair   = $this->item($biz3, ['item_code' => 'SCH-LIB-003', 'name' => 'Library Chair',                     'unit' => 'each', 'unit_cost' => 28.00,  'markup_percentage' => 20]);
        $libDesk    = $this->item($biz3, ['item_code' => 'SCH-LIB-004', 'name' => 'Librarian\'s Desk',                 'unit' => 'each', 'unit_cost' => 95.00,  'markup_percentage' => 20]);
        $primBooks  = $this->item($biz3, ['item_code' => 'SCH-LIB-005', 'name' => 'Primary Syllabus Book Set (Grades 1–7)', 'unit' => 'set', 'unit_cost' => 380.00, 'markup_percentage' => 20]);
        $secBooks   = $this->item($biz3, ['item_code' => 'SCH-LIB-006', 'name' => 'Secondary Syllabus Book Set (Forms 1–4)', 'unit' => 'set', 'unit_cost' => 480.00, 'markup_percentage' => 20]);
        $refBooks   = $this->item($biz3, ['item_code' => 'SCH-LIB-007', 'name' => 'Reference & Encyclopedia Set',      'unit' => 'set',  'unit_cost' => 290.00, 'markup_percentage' => 20]);
        $magRack    = $this->item($biz3, ['item_code' => 'SCH-LIB-008', 'name' => 'Newspaper & Magazine Rack',         'unit' => 'each', 'unit_cost' => 65.00,  'markup_percentage' => 20]);
        $globe      = $this->item($biz3, ['item_code' => 'SCH-LIB-009', 'name' => 'World Globe (30cm)',                'unit' => 'each', 'unit_cost' => 45.00,  'markup_percentage' => 20]);
        $wallMaps   = $this->item($biz3, ['item_code' => 'SCH-LIB-010', 'name' => 'Wall Maps Set (Zimbabwe + World)',  'unit' => 'set',  'unit_cost' => 55.00,  'markup_percentage' => 20]);

        $this->package($biz3, 'essential', 'Mini Reading Corner', 'A small reading corner with 100 books and basic shelving.', 1800.00, 360.00, 18, [
            [$bookshelf, 3], [$rdgTable, 2], [$libChair, 12], [$libDesk, 1], [$primBooks, 1], [$magRack, 1], [$globe, 1],
        ]);
        $this->package($biz3, 'intermediate', 'School Library Setup', 'A full library room for up to 50 readers with curriculum books and reference materials.', 5500.00, 1100.00, 24, [
            [$bookshelf, 8], [$rdgTable, 6], [$libChair, 36], [$libDesk, 1], [$primBooks, 3], [$secBooks, 2], [$refBooks, 1], [$magRack, 2], [$globe, 2], [$wallMaps, 2],
        ]);
        $this->package($biz3, 'advanced', 'Comprehensive Library', 'Full library with 1,500+ books, atlas collection, and comfortable seating for 80 students.', 11000.00, 2200.00, 24, [
            [$bookshelf, 16], [$rdgTable, 10], [$libChair, 60], [$libDesk, 2], [$primBooks, 6], [$secBooks, 5], [$refBooks, 3], [$magRack, 4], [$globe, 4], [$wallMaps, 4],
        ]);
        $this->package($biz3, 'premium', 'Digital Resource Library', 'Full library with 2,500+ books plus 10 reading tablets, study carrels and interactive globe.', 22000.00, 4400.00, 24, [
            [$bookshelf, 24], [$rdgTable, 14], [$libChair, 84], [$libDesk, 2], [$primBooks, 10], [$secBooks, 8], [$refBooks, 6], [$magRack, 6], [$globe, 6], [$wallMaps, 6],
        ]);
    }

    // ─── ECD & PRE-PRIMARY ────────────────────────────────────────────────────

    private function seedECDSchools(): void
    {
        $cat = $this->category('ECD & Pre-Primary', '🌱');

        $biz = $this->business(
            $cat,
            'ECD Classroom & Play Equipment',
            'Child-safe tables, chairs, learning toys and educational aids for ECD centres.',
            'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?auto=format&fit=crop&w=800&q=80'
        );

        $kTable   = $this->item($biz, ['item_code' => 'SCH-ECD-001', 'name' => "Children's Activity Table (seats 4)", 'unit' => 'each', 'unit_cost' => 65.00,  'markup_percentage' => 20]);
        $kChair   = $this->item($biz, ['item_code' => 'SCH-ECD-002', 'name' => "Children's Chair (age 3–6)",          'unit' => 'each', 'unit_cost' => 18.00,  'markup_percentage' => 20]);
        $wallABC  = $this->item($biz, ['item_code' => 'SCH-ECD-003', 'name' => 'Alphabet & Numbers Wall Chart Set',   'unit' => 'set',  'unit_cost' => 35.00,  'markup_percentage' => 20]);
        $toySet   = $this->item($biz, ['item_code' => 'SCH-ECD-004', 'name' => 'Educational Toys Box (age 3–6)',      'unit' => 'box',  'unit_cost' => 95.00,  'markup_percentage' => 20]);
        $sleepMat = $this->item($biz, ['item_code' => 'SCH-ECD-005', 'name' => 'Sleeping/Rest Mat',                   'unit' => 'each', 'unit_cost' => 12.00,  'markup_percentage' => 20]);
        $easel    = $this->item($biz, ['item_code' => 'SCH-ECD-006', 'name' => 'Double-Sided Drawing Easel',          'unit' => 'each', 'unit_cost' => 55.00,  'markup_percentage' => 20]);
        $storageBin=$this->item($biz, ['item_code' => 'SCH-ECD-007', 'name' => 'Plastic Storage Bin Set (10 bins)',  'unit' => 'set',  'unit_cost' => 45.00,  'markup_percentage' => 20]);
        $musInst  = $this->item($biz, ['item_code' => 'SCH-ECD-008', 'name' => 'Musical Instruments Set (rhythm band)','unit' => 'set', 'unit_cost' => 85.00,  'markup_percentage' => 20]);
        $puzzles  = $this->item($biz, ['item_code' => 'SCH-ECD-009', 'name' => 'Jigsaw Puzzles & Shape Sorters Set', 'unit' => 'set',  'unit_cost' => 55.00,  'markup_percentage' => 20]);
        $playDoh  = $this->item($biz, ['item_code' => 'SCH-ECD-010', 'name' => 'Play-Doh & Crafts Supply Kit',       'unit' => 'kit',  'unit_cost' => 40.00,  'markup_percentage' => 20]);
        $outdoorKit=$this->item($biz, ['item_code' => 'SCH-ECD-011', 'name' => 'Outdoor Play Equipment Set',         'unit' => 'set',  'unit_cost' => 350.00, 'markup_percentage' => 20]);
        $readBooks = $this->item($biz, ['item_code' => 'SCH-ECD-012', 'name' => 'Early Reader Books (set of 30)',    'unit' => 'set',  'unit_cost' => 90.00,  'markup_percentage' => 20]);

        $this->package($biz, 'essential', 'Starter ECD Kit (1 Room)', 'Basic setup for one ECD room of up to 15 children.', 1200.00, 240.00, 12, [
            [$kTable, 4], [$kChair, 16], [$wallABC, 1], [$toySet, 1], [$sleepMat, 15], [$storageBin, 1], [$readBooks, 1],
        ]);
        $this->package($biz, 'intermediate', 'Complete ECD Room', '1 fully equipped ECD room with learning, art and outdoor play for 25 children.', 3000.00, 600.00, 18, [
            [$kTable, 6], [$kChair, 25], [$wallABC, 2], [$toySet, 2], [$sleepMat, 25], [$easel, 2], [$storageBin, 2], [$musInst, 1], [$puzzles, 1], [$playDoh, 1], [$readBooks, 2],
        ]);
        $this->package($biz, 'advanced', 'Two-Room ECD Centre', '2 classrooms fully equipped plus outdoor play equipment.', 7500.00, 1500.00, 24, [
            [$kTable, 12], [$kChair, 50], [$wallABC, 4], [$toySet, 4], [$sleepMat, 50], [$easel, 4], [$storageBin, 4], [$musInst, 2], [$puzzles, 2], [$playDoh, 2], [$outdoorKit, 1], [$readBooks, 4],
        ]);
        $this->package($biz, 'premium', 'Full ECD School (4 Rooms)', '4 classrooms, outdoor play area, musical corner and a library reading nook.', 16000.00, 3200.00, 24, [
            [$kTable, 24], [$kChair, 100], [$wallABC, 8], [$toySet, 8], [$sleepMat, 100], [$easel, 8], [$storageBin, 8], [$musInst, 4], [$puzzles, 4], [$playDoh, 4], [$outdoorKit, 2], [$readBooks, 8],
        ]);
    }

    // ─── ADMINISTRATION & OFFICE ──────────────────────────────────────────────

    private function seedAdminOffice(): void
    {
        $cat = $this->category('Administration & Office', '🖥️');

        $biz = $this->business(
            $cat,
            'School Office & Admin Equipment',
            'Fully equip your school\'s administration block with office furniture, copiers and communication systems.',
            'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=800&q=80'
        );

        $offDesk    = $this->item($biz, ['item_code' => 'SCH-OFF-001', 'name' => 'Executive Office Desk',          'unit' => 'each', 'unit_cost' => 195.00, 'markup_percentage' => 20]);
        $offChair   = $this->item($biz, ['item_code' => 'SCH-OFF-002', 'name' => 'Executive Office Chair',         'unit' => 'each', 'unit_cost' => 110.00, 'markup_percentage' => 20]);
        $fileCab    = $this->item($biz, ['item_code' => 'SCH-OFF-003', 'name' => 'Filing Cabinet (4-drawer)',      'unit' => 'each', 'unit_cost' => 185.00, 'markup_percentage' => 20]);
        $safe       = $this->item($biz, ['item_code' => 'SCH-OFF-004', 'name' => 'Office Safe / Lockbox',          'unit' => 'each', 'unit_cost' => 230.00, 'markup_percentage' => 20]);
        $offPC      = $this->item($biz, ['item_code' => 'SCH-OFF-005', 'name' => 'Office Desktop Computer',        'unit' => 'each', 'unit_cost' => 480.00, 'markup_percentage' => 20]);
        $photocopy  = $this->item($biz, ['item_code' => 'SCH-OFF-006', 'name' => 'Photocopier / Printer (A3/A4)', 'unit' => 'each', 'unit_cost' => 1250.00,'markup_percentage' => 20]);
        $paSystem   = $this->item($biz, ['item_code' => 'SCH-OFF-007', 'name' => 'PA / Public Address System',    'unit' => 'set',  'unit_cost' => 380.00, 'markup_percentage' => 20]);
        $intercom   = $this->item($biz, ['item_code' => 'SCH-OFF-008', 'name' => 'School Intercom & Bell System', 'unit' => 'set',  'unit_cost' => 195.00, 'markup_percentage' => 20]);
        $stationery = $this->item($biz, ['item_code' => 'SCH-OFF-009', 'name' => 'Stationery Starter Kit',        'unit' => 'kit',  'unit_cost' => 95.00,  'markup_percentage' => 20]);
        $cctv       = $this->item($biz, ['item_code' => 'SCH-OFF-010', 'name' => 'CCTV Security Camera System (4 cams)', 'unit' => 'set', 'unit_cost' => 650.00, 'markup_percentage' => 20]);
        $waitChairs = $this->item($biz, ['item_code' => 'SCH-OFF-011', 'name' => 'Visitor Waiting Area Chairs (set of 6)', 'unit' => 'set', 'unit_cost' => 145.00, 'markup_percentage' => 20]);
        $staffBoard = $this->item($biz, ['item_code' => 'SCH-OFF-012', 'name' => 'Staff Notice Board (large)',    'unit' => 'each', 'unit_cost' => 55.00,  'markup_percentage' => 20]);

        $this->package($biz, 'essential', 'Head Office Starter', 'Basic setup for a headmaster\'s office — desk, chair, filing cabinet and computer.', 1800.00, 360.00, 18, [
            [$offDesk, 1], [$offChair, 1], [$fileCab, 1], [$safe, 1], [$offPC, 1], [$stationery, 1], [$staffBoard, 1],
        ]);
        $this->package($biz, 'intermediate', 'Full Admin Block (3 Offices)', 'Head, Deputy and Bursar offices fully equipped with shared photocopier and intercom.', 5500.00, 1100.00, 24, [
            [$offDesk, 3], [$offChair, 3], [$fileCab, 3], [$safe, 1], [$offPC, 3], [$photocopy, 1], [$intercom, 1], [$stationery, 2], [$waitChairs, 1], [$staffBoard, 2],
        ]);
        $this->package($biz, 'advanced', 'Admin Block with PA & Security', 'Full admin setup with public address system, CCTV and staff room furniture.', 11000.00, 2200.00, 24, [
            [$offDesk, 5], [$offChair, 5], [$fileCab, 5], [$safe, 2], [$offPC, 5], [$photocopy, 2], [$paSystem, 1], [$intercom, 1], [$cctv, 1], [$stationery, 3], [$waitChairs, 2], [$staffBoard, 4],
        ]);
        $this->package($biz, 'premium', 'Smart School Administration Suite', 'Complete admin infrastructure — all offices, PA, CCTV, boardroom and biometric access control.', 22000.00, 4400.00, 24, [
            [$offDesk, 8], [$offChair, 8], [$fileCab, 8], [$safe, 3], [$offPC, 8], [$photocopy, 3], [$paSystem, 2], [$intercom, 2], [$cctv, 2], [$stationery, 6], [$waitChairs, 4], [$staffBoard, 6],
        ]);
    }
}
