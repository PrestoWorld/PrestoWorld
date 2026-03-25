<?php

/**
 * Seed Static Pages — All Locales
 *
 * Locales: en, vi, ja, ko, fr
 * Luồng: Tạo/clone data en sang bảng chính → link tất cả bản dịch qua FK.
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use Cake\Chronos\Chronos;

$app = require dirname(__DIR__) . '/bootstrap/app.php';
$app->boot();

$db = $app->make(Cycle\Database\DatabaseProviderInterface::class)->database();

$pages = [
    // ─────────────────────────────────────────────────────────────────────────
    // ABOUT
    // ─────────────────────────────────────────────────────────────────────────
    [
        'slug'   => 'about',
        'status' => 'publish',
        'translations' => [
            'en' => [
                'title'           => 'About Us',
                'content'         => 'PrestoWorld operates on a 100% remote model — transparent, flexible, and unrestricted by geography. Our team members work from countries around the world, united by a single purpose: to deliver real value to our customers.' . "\n\n" . 'We have no fixed headquarters — and that is a deliberate choice. A globally distributed model allows us to attract the best talent, wherever they may be. In the future, we may establish offices in strategic locations as our scale and business needs evolve.' . "\n\n" . 'Today, we channel our full energy into two core disciplines: technical excellence and customer support — the twin pillars of our team\'s strength and our commitment to every client we serve.',
                'seo_title'       => 'About PrestoWorld – 100% Remote Global Team',
                'seo_description' => 'PrestoWorld is a fully remote, globally distributed team specializing in technical excellence and customer support.',
            ],
            'vi' => [
                'title'           => 'Về chúng tôi',
                'content'         => 'PrestoWorld vận hành theo mô hình 100% remote — minh bạch, linh hoạt và không giới hạn bởi địa lý. Các thành viên trong đội ngũ của chúng tôi làm việc từ nhiều quốc gia khác nhau trên thế giới, kết nối bởi cùng một mục tiêu: mang lại giá trị thực sự cho khách hàng.' . "\n\n" . 'Chúng tôi không có trụ sở cố định — và đó là lựa chọn có chủ đích. Mô hình phân tán toàn cầu cho phép chúng tôi tuyển chọn những người giỏi nhất, bất kể họ đang ở đâu. Trong tương lai, chúng tôi có thể mở văn phòng tại một số địa điểm chiến lược khi quy mô và nhu cầu kinh doanh cho phép.' . "\n\n" . 'Hiện tại, chúng tôi tập trung toàn lực vào hai lĩnh vực cốt lõi là kỹ thuật và hỗ trợ khách hàng — đây là thế mạnh của đội ngũ và là cam kết của chúng tôi với từng khách hàng.',
                'seo_title'       => 'Về PrestoWorld – Đội ngũ Global 100% Remote',
                'seo_description' => 'PrestoWorld vận hành 100% remote với đội ngũ kỹ thuật và support toàn cầu. Minh bạch, chuyên nghiệp và tận tâm với từng khách hàng.',
            ],
            'ja' => [
                'title'           => '私たちについて',
                'content'         => 'PrestoWorld は完全リモートモデルで運営されています。透明性が高く、柔軟で、地理的な制約を受けません。私たちのチームメンバーは世界中のさまざまな国で活動し、ひとつの目標に向かって結束しています。それは、お客様に真の価値を届けることです。' . "\n\n" . '固定の本社を持たないのは、意図的な選択です。グローバルに分散したチーム体制により、場所を問わず最高の人材を集めることができます。将来的には、事業規模やニーズに応じて戦略的な拠点にオフィスを開設する可能性があります。' . "\n\n" . '現在は、技術力とカスタマーサポートという二つのコア領域に全力を注いでいます。これが私たちの強みであり、すべてのお客様へのコミットメントです。',
                'seo_title'       => 'PrestoWorld について – 100% リモートのグローバルチーム',
                'seo_description' => 'PrestoWorld は、技術とサポートに特化した完全リモートのグローバルチームです。',
            ],
            'ko' => [
                'title'           => '회사 소개',
                'content'         => 'PrestoWorld는 100% 원격 근무 모델로 운영됩니다. 투명하고 유연하며, 지리적 경계에 제한받지 않습니다. 저희 팀원들은 전 세계 여러 나라에서 근무하며, 하나의 목표를 공유합니다. 바로 고객에게 실질적인 가치를 전달하는 것입니다.' . "\n\n" . '고정 본사가 없는 것은 의도적인 선택입니다. 글로벌 분산 모델을 통해 지역에 관계없이 최고의 인재를 확보할 수 있습니다. 향후 사업 규모와 필요에 따라 전략적 위치에 사무소를 개설할 수도 있습니다.' . "\n\n" . '현재 저희는 기술 역량과 고객 지원이라는 두 가지 핵심 분야에 전력을 집중하고 있습니다. 이것이 저희 팀의 강점이자 모든 고객에 대한 약속입니다.',
                'seo_title'       => 'PrestoWorld 소개 – 100% 원격 글로벌 팀',
                'seo_description' => 'PrestoWorld는 기술 및 고객 지원에 특화된 완전 원격 글로벌 팀입니다.',
            ],
            'fr' => [
                'title'           => 'À propos de nous',
                'content'         => 'PrestoWorld fonctionne selon un modèle entièrement en télétravail — transparent, flexible et affranchi des contraintes géographiques. Les membres de notre équipe travaillent depuis de nombreux pays à travers le monde, unis par un seul objectif : apporter une valeur concrète à nos clients.' . "\n\n" . 'L\'absence de siège fixe est un choix délibéré. Un modèle distribué à l\'échelle mondiale nous permet de recruter les meilleurs talents, où qu\'ils se trouvent. À l\'avenir, nous pourrons ouvrir des bureaux dans des emplacements stratégiques en fonction de l\'évolution de nos activités.' . "\n\n" . 'Aujourd\'hui, nous concentrons toute notre énergie sur deux disciplines fondamentales : l\'excellence technique et le support client — les deux piliers de notre équipe et de notre engagement envers chacun de nos clients.',
                'seo_title'       => 'À propos de PrestoWorld – Équipe mondiale 100% remote',
                'seo_description' => 'PrestoWorld est une équipe mondiale entièrement distribuée, spécialisée dans l\'excellence technique et le support client.',
            ],
        ],
    ],

    // ─────────────────────────────────────────────────────────────────────────
    // CONTACT
    // ─────────────────────────────────────────────────────────────────────────
    [
        'slug'   => 'contact',
        'status' => 'publish',
        'translations' => [
            'en' => [
                'title'           => 'Contact Us',
                'content'         => 'As a fully remote team, the best way to reach us is through email or our online support channels. Whether you have a technical question, a partnership inquiry, or simply want to learn more about what we do — we\'re here and ready to help.' . "\n\n" . 'Our team operates across multiple time zones, so you can expect a thoughtful response from us, no matter when you reach out.',
                'seo_title'       => 'Contact PrestoWorld',
                'seo_description' => 'Get in touch with the PrestoWorld team for technical support, partnerships, or general inquiries.',
            ],
            'vi' => [
                'title'           => 'Liên hệ',
                'content'         => 'Là một đội ngũ hoàn toàn làm việc online, cách tốt nhất để liên hệ với chúng tôi là qua email hoặc các kênh hỗ trợ trực tuyến. Dù bạn có câu hỏi kỹ thuật, muốn hợp tác hay đơn giản là muốn tìm hiểu thêm về những gì chúng tôi đang làm — chúng tôi luôn sẵn sàng.' . "\n\n" . 'Đội ngũ của chúng tôi hoạt động trải dài nhiều múi giờ, vì vậy bạn có thể mong đợi phản hồi chu đáo từ chúng tôi dù liên hệ vào bất kỳ thời điểm nào.',
                'seo_title'       => 'Liên hệ PrestoWorld',
                'seo_description' => 'Liên hệ với đội ngũ PrestoWorld để được hỗ trợ kỹ thuật, hợp tác kinh doanh hoặc giải đáp thắc mắc.',
            ],
            'ja' => [
                'title'           => 'お問い合わせ',
                'content'         => '完全リモートチームである私たちへのご連絡は、メールまたはオンラインサポートチャネルをご利用いただくのが最善です。技術的なご質問、パートナーシップのお問い合わせ、またはサービスについての情報収集など、いかなるご用件でもお気軽にどうぞ。' . "\n\n" . 'チームは複数のタイムゾーンにまたがって活動していますので、いつご連絡いただいても丁寧に対応いたします。',
                'seo_title'       => 'お問い合わせ – PrestoWorld',
                'seo_description' => '技術サポートやパートナーシップのご相談など、PrestoWorld チームへのお問い合わせはこちら。',
            ],
            'ko' => [
                'title'           => '문의하기',
                'content'         => '완전 원격 팀인 저희에게 연락하는 가장 좋은 방법은 이메일 또는 온라인 지원 채널을 이용하는 것입니다. 기술적인 질문, 파트너십 문의, 또는 저희 서비스에 대해 더 알고 싶으신 경우 언제든지 연락해 주세요.' . "\n\n" . '저희 팀은 여러 시간대에 걸쳐 운영되므로, 언제 연락하셔도 성실한 답변을 드릴 수 있습니다.',
                'seo_title'       => '문의하기 – PrestoWorld',
                'seo_description' => '기술 지원, 파트너십 또는 일반 문의를 위해 PrestoWorld 팀에 연락하세요.',
            ],
            'fr' => [
                'title'           => 'Nous contacter',
                'content'         => 'En tant qu\'équipe entièrement en télétravail, le meilleur moyen de nous joindre est par e-mail ou via nos canaux de support en ligne. Que vous ayez une question technique, une demande de partenariat ou simplement envie d\'en savoir plus sur ce que nous faisons — nous sommes là pour vous.' . "\n\n" . 'Notre équipe opère sur plusieurs fuseaux horaires, vous pouvez donc vous attendre à une réponse attentive de notre part, quel que soit le moment où vous nous contactez.',
                'seo_title'       => 'Contactez PrestoWorld',
                'seo_description' => 'Contactez l\'équipe PrestoWorld pour un support technique, un partenariat ou toute autre demande.',
            ],
        ],
    ],

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVACY POLICY
    // ─────────────────────────────────────────────────────────────────────────
    [
        'slug'   => 'privacy-policy',
        'status' => 'publish',
        'translations' => [
            'en' => [
                'title'           => 'Privacy Policy',
                'content'         => 'At PrestoWorld, the privacy and security of your personal information is a fundamental responsibility we take seriously.' . "\n\n" . 'We collect only the data necessary to provide and improve our services. This may include your name, email address, and usage data. We never sell, rent, or trade your personal information with third parties.' . "\n\n" . 'All data is stored securely and accessed only by authorized team members for service delivery purposes. You have the right to request access to, correction of, or deletion of your personal data at any time by contacting us directly.',
                'seo_title'       => 'Privacy Policy – PrestoWorld',
                'seo_description' => 'Read the PrestoWorld privacy policy to understand how we collect, use, and protect your personal data.',
            ],
            'vi' => [
                'title'           => 'Chính sách bảo mật',
                'content'         => 'Tại PrestoWorld, quyền riêng tư và bảo mật thông tin cá nhân của bạn là trách nhiệm cốt lõi mà chúng tôi đặt lên hàng đầu.' . "\n\n" . 'Chúng tôi chỉ thu thập dữ liệu cần thiết để cung cấp và cải thiện dịch vụ, bao gồm tên, địa chỉ email và dữ liệu sử dụng. Chúng tôi không bao giờ bán, cho thuê hoặc trao đổi thông tin cá nhân của bạn với bên thứ ba.' . "\n\n" . 'Mọi dữ liệu được lưu trữ bảo mật và chỉ được truy cập bởi các thành viên được ủy quyền phục vụ mục đích cung cấp dịch vụ. Bạn có quyền yêu cầu truy cập, chỉnh sửa hoặc xóa dữ liệu cá nhân của mình bất kỳ lúc nào bằng cách liên hệ trực tiếp với chúng tôi.',
                'seo_title'       => 'Chính sách bảo mật – PrestoWorld',
                'seo_description' => 'Đọc chính sách bảo mật của PrestoWorld để hiểu cách chúng tôi thu thập, sử dụng và bảo vệ dữ liệu cá nhân.',
            ],
            'ja' => [
                'title'           => 'プライバシーポリシー',
                'content'         => 'PrestoWorld では、お客様の個人情報のプライバシーと安全性を最重要事項として取り組んでいます。' . "\n\n" . '私たちが収集するのは、サービスの提供と改善に必要なデータのみです。これには氏名、メールアドレス、利用データなどが含まれます。お客様の個人情報を第三者に販売・貸与・売買することは一切ありません。' . "\n\n" . 'すべてのデータは安全に保管され、サービス提供を目的として許可を得たチームメンバーのみがアクセスできます。お客様はいつでも、ご自身の個人データへのアクセス、修正、または削除をご要望いただけます。',
                'seo_title'       => 'プライバシーポリシー – PrestoWorld',
                'seo_description' => 'PrestoWorld のプライバシーポリシーをご確認ください。個人データの収集・利用・保護の方針について説明しています。',
            ],
            'ko' => [
                'title'           => '개인정보 처리방침',
                'content'         => 'PrestoWorld는 귀하의 개인정보 보호와 보안을 핵심 책임으로 여기며 최우선으로 다루고 있습니다.' . "\n\n" . '저희는 서비스 제공 및 개선에 필요한 데이터만을 수집합니다. 여기에는 이름, 이메일 주소 및 이용 데이터가 포함될 수 있습니다. 귀하의 개인정보를 제3자에게 판매, 임대 또는 제공하는 일은 절대 없습니다.' . "\n\n" . '모든 데이터는 안전하게 저장되며, 서비스 제공 목적으로 권한을 부여받은 팀원만 접근할 수 있습니다. 언제든지 저희에게 직접 연락하여 개인 데이터의 열람, 수정 또는 삭제를 요청하실 수 있습니다.',
                'seo_title'       => '개인정보 처리방침 – PrestoWorld',
                'seo_description' => 'PrestoWorld의 개인정보 처리방침을 읽고 개인 데이터 수집, 사용 및 보호 방법을 확인하세요.',
            ],
            'fr' => [
                'title'           => 'Politique de confidentialité',
                'content'         => 'Chez PrestoWorld, la confidentialité et la sécurité de vos informations personnelles constituent une responsabilité fondamentale que nous prenons très au sérieux.' . "\n\n" . 'Nous ne collectons que les données strictement nécessaires à la fourniture et à l\'amélioration de nos services. Cela peut inclure votre nom, votre adresse e-mail et vos données d\'utilisation. Nous ne vendons, ne louons ni n\'échangeons jamais vos informations personnelles avec des tiers.' . "\n\n" . 'Toutes les données sont stockées de manière sécurisée et ne sont accessibles qu\'aux membres autorisés de l\'équipe, dans le cadre de la fourniture du service. Vous disposez du droit d\'accéder à vos données, de les corriger ou de les supprimer à tout moment en nous contactant directement.',
                'seo_title'       => 'Politique de confidentialité – PrestoWorld',
                'seo_description' => 'Lisez la politique de confidentialité de PrestoWorld pour comprendre comment nous collectons, utilisons et protégeons vos données personnelles.',
            ],
        ],
    ],
];

// ─────────────────────────────────────────────────────────────────────────────
// Seed
// ─────────────────────────────────────────────────────────────────────────────
$defaultLocale = 'en';

foreach ($pages as $pageConfig) {
    $slug   = $pageConfig['slug'];
    $status = $pageConfig['status'];

    $defaultTrans = $pageConfig['translations'][$defaultLocale] ?? reset($pageConfig['translations']);

    $mainData = [
        'title'      => $defaultTrans['title'],
        'slug'       => $slug,
        'content'    => $defaultTrans['content'],
        'status'     => $status,
        'created_at' => Chronos::now()->format('Y-m-d H:i:s'),
    ];

    $exists = $db->table('presto_static_pages')
        ->where('slug', $slug)
        ->run()
        ->fetch();

    if (!$exists) {
        $id = $db->insert('presto_static_pages')->values($mainData)->run();
        echo "[OK] Seeded main: {$defaultTrans['title']}\n";
    } else {
        $id = $exists['id'];
        unset($mainData['created_at']);
        $db->update('presto_static_pages', $mainData, ['id' => $id])->run();
        echo "[OK] Updated main: {$defaultTrans['title']}\n";
    }

    foreach ($pageConfig['translations'] as $lang => $transData) {
        $transRecord = [
            'static_page_id'  => $id,
            'language'        => $lang,
            'title'           => $transData['title'],
            'content'         => $transData['content'],
            'seo_title'       => $transData['seo_title'] ?? null,
            'seo_description' => $transData['seo_description'] ?? null,
        ];

        $transExists = $db->table('presto_translations_static_pages')
            ->where('static_page_id', $id)
            ->where('language', $lang)
            ->count();

        if ($transExists === 0) {
            $db->insert('presto_translations_static_pages')->values($transRecord)->run();
            echo "  -> Seeded [{$lang}]: {$transData['title']}\n";
        } else {
            unset($transRecord['static_page_id'], $transRecord['language']);
            $db->update('presto_translations_static_pages', $transRecord, [
                'static_page_id' => $id,
                'language'       => $lang,
            ])->run();
            echo "  -> Updated [{$lang}]: {$transData['title']}\n";
        }
    }
}

echo "\nDone!\n";
