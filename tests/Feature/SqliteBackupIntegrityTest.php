<?php

namespace Tests\Feature;

use App\Services\BackupService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * النسخة الاحتياطية لقاعدة sqlite لقطةٌ متماسكة، لا نسخَ ملفٍ خام.
 *
 * النظام يعمل على جهاز في الفندق، والنسخة هي كل ما يبقى إن عُطب القرص أو
 * أُلغيت الاستضافة. وكان التفريغ copy() للملف: يقرأه بينما قد تكون معاملةٌ
 * قيد التنفيذ فيحمل حالةً ممزّقة، ومع journal_mode=wal تكون آخر العمليات في
 * ملف -wal لا في الملف المنسوخ — فتضيع بلا أثر، والنسخة تبدو سليمة.
 */
class SqliteBackupIntegrityTest extends TestCase
{
    private string $dbPath;
    private string $workDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workDir = storage_path('app/backup-test-' . getmypid());
        $this->dbPath  = $this->workDir . '/live.sqlite';
        File::ensureDirectoryExists($this->workDir);
        File::put($this->dbPath, '');

        // الاختبارات تعمل على :memory:، والتفريغ يحتاج ملفاً حقيقياً — وهو
        // أيضاً الشرط الذي يجعل wal مُفعَّلاً فعلاً (لا معنى له في الذاكرة)
        config(['database.connections.sqlite.database' => $this->dbPath]);
        DB::purge('sqlite');

        DB::connection('sqlite')->statement('CREATE TABLE payments (id integer primary key, note text)');
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');
        File::deleteDirectory($this->workDir);

        parent::tearDown();
    }

    public function test_the_local_database_runs_in_wal_with_a_busy_timeout(): void
    {
        $this->assertSame('wal', strtolower((string) DB::connection('sqlite')->select('PRAGMA journal_mode')[0]->journal_mode));
        $this->assertSame(5000, (int) DB::connection('sqlite')->select('PRAGMA busy_timeout')[0]->timeout);
    }

    /**
     * الاختبار الحقيقي: صفٌّ كُتب للتوّ ولم يُنقل بعد من ملف -wal إلى القاعدة.
     * النسخ الخام يفقده، والنسخة الصحيحة تحمله.
     */
    public function test_a_row_still_in_the_wal_survives_the_backup(): void
    {
        DB::connection('sqlite')->insert("INSERT INTO payments (note) VALUES ('مستلمة قبل انقطاع الكهرباء')");

        $dump = app(BackupService::class)->dumpDatabase($this->workDir, 'now');

        $restored = new \PDO('sqlite:' . $dump);
        $this->assertSame(
            'مستلمة قبل انقطاع الكهرباء',
            $restored->query('SELECT note FROM payments')->fetchColumn()
        );
    }

    /** وللاختبار أسنان: نسخُ الملف خاماً في نفس اللحظة يفقد الصف. */
    public function test_a_raw_file_copy_would_have_lost_it(): void
    {
        DB::connection('sqlite')->insert("INSERT INTO payments (note) VALUES ('مستلمة')");

        $raw = $this->workDir . '/raw-copy.sqlite';
        copy($this->dbPath, $raw);

        $copied = new \PDO('sqlite:' . $raw);
        $copied->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        // إما جدولٌ غائب أو صفٌّ مفقود — المهم أن النسخ الخام لا يُعتمد عليه
        $lost = false;
        try {
            $lost = $copied->query('SELECT COUNT(*) FROM payments')->fetchColumn() == 0;
        } catch (\PDOException) {
            $lost = true;
        }

        $this->assertTrue($lost, 'النسخ الخام حمل الصف — فالاختبار لا يُثبت شيئاً');
    }

    /** ولا تُفقد النسخة عند تكرار التفريغ (الوجهة تُستبدل لا تُرفض). */
    public function test_dumping_twice_in_a_row_works(): void
    {
        DB::connection('sqlite')->insert("INSERT INTO payments (note) VALUES ('أولى')");

        $first = app(BackupService::class)->dumpDatabase($this->workDir, 'a');
        $second = app(BackupService::class)->dumpDatabase($this->workDir, 'b');

        $this->assertFileExists($first);
        $this->assertFileExists($second);
        $this->assertNotSame($first, $second);
    }
}
