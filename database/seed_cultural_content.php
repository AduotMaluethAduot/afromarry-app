<?php
/**
 * Seed script for cultural content categories and articles
 */

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Insert cultural categories
    $categories = [
        ['name' => 'Wedding Traditions', 'description' => 'Traditional wedding ceremonies and customs', 'icon' => 'fas fa-ring', 'color' => '#8B5CF6'],
        ['name' => 'Dowry Practices', 'description' => 'Dowry customs and traditions across African cultures', 'icon' => 'fas fa-gift', 'color' => '#EC4899'],
        ['name' => 'Ceremonial Attire', 'description' => 'Traditional wedding clothing and accessories', 'icon' => 'fas fa-tshirt', 'color' => '#10B981'],
        ['name' => 'Music & Dance', 'description' => 'Traditional music and dance in wedding ceremonies', 'icon' => 'fas fa-music', 'color' => '#F59E0B'],
        ['name' => 'Food & Cuisine', 'description' => 'Traditional wedding foods and culinary customs', 'icon' => 'fas fa-utensils', 'color' => '#EF4444']
    ];
    
    foreach ($categories as $category) {
        $query = "INSERT IGNORE INTO cultural_categories (name, description, icon, color, is_active, sort_order) 
                 VALUES (:name, :description, :icon, :color, :is_active, :sort_order)";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':name' => $category['name'],
            ':description' => $category['description'],
            ':icon' => $category['icon'],
            ':color' => $category['color'],
            ':is_active' => true,
            ':sort_order' => array_search($category, $categories)
        ]);
    }
    
    echo "Cultural categories seeded successfully.\n";
    
    // Get admin user ID for authoring articles
    $query = "SELECT id FROM users WHERE email = 'admin@afromarry.com'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if (!$admin) {
        echo "Admin user not found. Creating articles with user ID 1.\n";
        $author_id = 1;
    } else {
        $author_id = $admin['id'];
    }
    
    // Insert sample articles
    $articles = [
        [
            'category_id' => 1,
            'title' => 'The Sacred Rites of Yoruba Wedding Ceremonies',
            'slug' => 'yoruba-wedding-ceremonies',
            'excerpt' => 'Discover the profound spiritual significance of traditional Yoruba wedding rituals',
            'content' => '<p>Yoruba wedding ceremonies are among the most elaborate and spiritually significant marriage traditions in Africa. These ceremonies are deeply rooted in the Yoruba belief system, which emphasizes the connection between the physical and spiritual worlds.</p>
                          <p>The traditional Yoruba wedding consists of several distinct ceremonies, each with its own purpose and meaning:</p>
                          <h2>1. Introduction Ceremony (Mo mi mo e)</h2>
                          <p>This initial ceremony involves the groom\'s family formally introducing themselves to the bride\'s family. The purpose is to establish a relationship between the two families and seek their blessing for the union.</p>
                          <h2>2. Prayer Ceremony (Igba N\'Ewa)</h2>
                          <p>The spiritual aspect of the wedding begins with prayers offered by traditional priests and elders. These prayers invoke the blessings of ancestors and deities for the couple\'s marriage.</p>
                          <h2>3. Traditional Wedding (Igba Ewu)</h2>
                          <p>The main wedding ceremony where the couple exchanges vows and is officially pronounced husband and wife according to Yoruba customs. This includes the tying of the knot with a special cloth and the sharing of a ceremonial drink.</p>
                          <p>These ceremonies not only unite two individuals but also strengthen the bonds between two families and communities, preserving centuries-old traditions that continue to thrive today.</p>',
            'featured_image' => 'https://d64gsuwffb70l.cloudfront.net/68fcfebef8ea68585421298c_1761410855685_37193717.webp',
            'is_published' => true
        ],
        [
            'category_id' => 2,
            'title' => 'Understanding Lobola: The Zulu Dowry Tradition',
            'slug' => 'zulu-lobola-tradition',
            'excerpt' => 'Explore the cultural significance and modern adaptations of lobola in Zulu society',
            'content' => '<p>Lobola, the Zulu practice of paying a bride price, is one of the most recognized African marriage customs worldwide. Far from being a simple transaction, lobola represents deep cultural values and social responsibilities.</p>
                          <h2>Cultural Significance</h2>
                          <p>Lobola serves multiple purposes in Zulu society:</p>
                          <ul>
                              <li><strong>Respect for the Bride\'s Family:</strong> It acknowledges the investment the bride\'s family has made in raising her.</li>
                              <li><strong>Social Recognition:</strong> It publicly validates the marriage and establishes the groom\'s commitment.</li>
                              <li><strong>Family Bonding:</strong> It creates lasting relationships between the two families.</li>
                              <li><strong>Economic Support:</strong> Historically, it provided financial support to the bride\'s family.</li>
                          </ul>
                          <h2>Traditional Components</h2>
                          <p>Traditionally, lobola consists of:</p>
                          <ol>
                              <li><strong>Cattle:</strong> The primary component, often ranging from 10-20 head depending on family status.</li>
                              <li><strong>Monetary Equivalent:</strong> Modern adaptations often use cash equivalent to cattle value.</li>
                              <li><strong>Gift Items:</strong> Additional items like blankets, clothing, and household goods.</li>
                          </ol>
                          <p>Today, many couples negotiate lobola based on mutual understanding, often combining traditional elements with modern financial realities while preserving the cultural essence of this important tradition.</p>',
            'featured_image' => 'https://d64gsuwffb70l.cloudfront.net/68fcfebef8ea68585421298c_1761410857432_8d2f5127.webp',
            'is_published' => true
        ],
        [
            'category_id' => 3,
            'title' => 'The Elegance of Ashanti Kente Cloth in Weddings',
            'slug' => 'ashanti-kente-wedding-cloth',
            'excerpt' => 'Discover how the royal Ashanti kente cloth transforms wedding ceremonies',
            'content' => '<p>Kente cloth, originating from the Ashanti Kingdom of Ghana, represents one of Africa\'s most prestigious textile traditions. When incorporated into wedding ceremonies, kente cloth adds unparalleled elegance and cultural significance.</p>
                          <h2>Historical Significance</h2>
                          <p>Originally reserved for royalty and special occasions, kente cloth is handwoven by skilled artisans using traditional techniques passed down through generations. Each pattern and color combination carries specific meanings and tells stories of Ashanti heritage.</p>
                          <h2>Wedding Applications</h2>
                          <p>In modern African weddings, kente cloth is used in various ways:</p>
                          <ul>
                              <li><strong>Bride\'s Attire:</strong> Traditional kente gowns or wraps that showcase the bride\'s cultural pride.</li>
                              <li><strong>Groom\'s Outfit:</strong> Kente vests, jackets, or full traditional attire for the groom.</li>
                              <li><strong>Wedding Party:</strong> Coordinated kente accessories for bridesmaids and groomsmen.</li>
                              <li><strong>Ceremonial Decor:</strong> Kente cloth used as altar coverings, aisle runners, or decorative elements.</li>
                          </ul>
                          <h2>Color Symbolism</h2>
                          <p>Different kente patterns feature specific color combinations with symbolic meanings:</p>
                          <ul>
                              <li><strong>Gold:</strong> Wealth, royalty, and spiritual purity</li>
                              <li><strong>Green:</strong> Growth, harmony, and fertility</li>
                              <li><strong>Blue:</strong> Peace, harmony, and spiritual purity</li>
                              <li><strong>Red:</strong> Blood, sacrifice, and strong emotions</li>
                          </ul>
                          <p>Incorporating authentic kente cloth into wedding ceremonies not only adds visual splendor but also honors centuries of African craftsmanship and cultural heritage.</p>',
            'featured_image' => 'https://d64gsuwffb70l.cloudfront.net/68fcfebef8ea68585421298c_1761410823359_9055dec3.webp',
            'is_published' => true
        ]
    ];
    
    foreach ($articles as $article) {
        // Check if article already exists
        $query = "SELECT id FROM cultural_articles WHERE slug = :slug";
        $stmt = $db->prepare($query);
        $stmt->execute([':slug' => $article['slug']]);
        
        if (!$stmt->fetch()) {
            $query = "INSERT INTO cultural_articles (category_id, title, slug, excerpt, content, featured_image, author_id, is_published, published_at) 
                     VALUES (:category_id, :title, :slug, :excerpt, :content, :featured_image, :author_id, :is_published, :published_at)";
            
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':category_id' => $article['category_id'],
                ':title' => $article['title'],
                ':slug' => $article['slug'],
                ':excerpt' => $article['excerpt'],
                ':content' => $article['content'],
                ':featured_image' => $article['featured_image'],
                ':author_id' => $author_id,
                ':is_published' => $article['is_published'],
                ':published_at' => date('Y-m-d H:i:s')
            ]);
            
            echo "Article '{$article['title']}' seeded successfully.\n";
        } else {
            echo "Article '{$article['title']}' already exists.\n";
        }
    }
    
    echo "Cultural content seeding completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>