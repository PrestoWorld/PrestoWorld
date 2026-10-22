<?php

declare(strict_types=1);

use Cycle\Database\DatabaseInterface;
use Cycle\Database\Table;
use PrestoWorld\Database\MigrationInterface;

/**
 * 2026_10_22_000003_content_taxonomy_tables
 *
 * Bảng nội dung - taxonomy - dịch thuật của lõi PrestoCMS.
 *
 * Những bảng này trước đây chỉ được tạo bởi PostTypeSchemaManager khi một
 * module đăng ký post type hoặc taxonomy (live migration). Không module nào
 * đăng ký ở trạng thái mặc định nên chúng không bao giờ tồn tại, trong khi
 * PostRepository và MediaController đọc/ghi chúng ngay.
 *
 * - terms / term_relationships: taxonomy hub + cầu nối post <-> term
 * - postmeta: meta của post (MediaController)
 * - post_translations: giá trị đã dịch theo locale
 * - icl_translations: chỉ mục nhóm dịch (tương thích WPML)
 *
 * Schema bám sát PostTypeSchemaManager để live migration không tạo lệch.
 */
return new class implements MigrationInterface {
    public function up(DatabaseInterface $db, string $prefix): void
    {
        $this->postsTrid($db, $prefix);
        $this->terms($db, $prefix);
        $this->termRelationships($db, $prefix);
        $this->postMeta($db, $prefix);
        $this->postTranslations($db, $prefix);
        $this->iclTranslations($db, $prefix);
    }

    public function down(DatabaseInterface $db, string $prefix): void
    {
        $tables = [
            'icl_translations',
            'post_translations',
            'postmeta',
            'term_relationships',
            'terms',
        ];

        foreach ($tables as $name) {
            $full = $prefix . $name;
            if (!$db->hasTable($full)) {
                continue;
            }
            $table = $db->table($full);
            if ($table instanceof Table) {
                $schema = $table->getSchema();
                $schema->declareDropped();
                $schema->save();
            }
        }
    }

    /**
     * Nhóm dịch của một bài viết được lưu trên chính dòng post (trid).
     * PostTypeSchemaManager thêm cột này khi live migration chạy, nhưng
     * PostRepository cần nó cho mọi bản dịch nên phải có sẵn.
     */
    private function postsTrid(DatabaseInterface $db, string $prefix): void
    {
        $name = $prefix . 'posts';
        if (!$db->hasTable($name)) {
            return;
        }

        $table = $db->table($name);
        if (!$table instanceof Table) {
            return;
        }

        $schema = $table->getSchema();

        if (!$schema->hasColumn('trid')) {
            $schema->column('trid')->bigInteger()->nullable()->index();
            $schema->save();
        }
    }

    private function terms(DatabaseInterface $db, string $prefix): void
    {
        $name = $prefix . 'terms';
        if ($db->hasTable($name)) {
            return;
        }

        $table = $db->table($name);
        if (!$table instanceof Table) {
            return;
        }

        $schema = $table->getSchema();
        $schema->primary('id');
        $schema->column('taxonomy')->string(32)->index();
        $schema->column('name')->string(255);
        $schema->column('slug')->string(255)->index();
        $schema->column('count')->integer()->defaultValue(0);
        $schema->save();
    }

    private function termRelationships(DatabaseInterface $db, string $prefix): void
    {
        $name = $prefix . 'term_relationships';
        if ($db->hasTable($name)) {
            return;
        }

        $table = $db->table($name);
        if (!$table instanceof Table) {
            return;
        }

        $schema = $table->getSchema();
        $schema->column('object_id')->integer()->index();
        $schema->column('term_id')->integer()->index();
        $schema->index(['object_id', 'term_id'])->unique();
        $schema->save();
    }

    private function postMeta(DatabaseInterface $db, string $prefix): void
    {
        $name = $prefix . 'postmeta';
        if ($db->hasTable($name)) {
            return;
        }

        $table = $db->table($name);
        if (!$table instanceof Table) {
            return;
        }

        $schema = $table->getSchema();
        $schema->primary('id');
        $schema->column('post_id')->integer()->index();
        $schema->column('meta_key')->string(191)->index();
        $schema->column('meta_value')->type('longtext')->nullable();
        $schema->index(['post_id', 'meta_key']);
        $schema->save();
    }

    private function postTranslations(DatabaseInterface $db, string $prefix): void
    {
        $name = $prefix . 'post_translations';
        if ($db->hasTable($name)) {
            return;
        }

        $table = $db->table($name);
        if (!$table instanceof Table) {
            return;
        }

        $schema = $table->getSchema();
        $schema->primary('id');
        $schema->column('post_id')->integer()->nullable(false);
        $schema->column('locale')->string(5)->nullable(false);
        $schema->column('title')->string(255)->nullable();
        $schema->column('slug')->string(255)->nullable();
        $schema->column('content')->text()->nullable();
        $schema->column('excerpt')->text()->nullable();
        $schema->index(['post_id']);
        $schema->index(['post_id', 'locale'])->unique();
        $schema->save();
    }

    private function iclTranslations(DatabaseInterface $db, string $prefix): void
    {
        $name = $prefix . 'icl_translations';
        if ($db->hasTable($name)) {
            return;
        }

        $table = $db->table($name);
        if (!$table instanceof Table) {
            return;
        }

        $schema = $table->getSchema();
        $schema->primary('translation_id');
        $schema->column('element_type')->string(60)->nullable(false);
        $schema->column('element_id')->integer()->nullable(false);
        $schema->column('trid')->bigInteger()->nullable(false);
        $schema->column('language_code')->string(7)->nullable(false);
        $schema->column('source_language_code')->string(7)->nullable();
        $schema->index(['element_type', 'element_id', 'language_code'])->unique();
        $schema->index(['trid']);
        $schema->index(['language_code']);
        $schema->save();
    }
};
