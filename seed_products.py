import os
from sqlalchemy import text
from database import engine

# Dummy product data for Swarnalaya Jewellery
products = [
    {
        "name": "Celestial Diadem Ring",
        "description": "A stunning 18k white gold ring featuring a 2-carat central diamond surrounded by a halo of micro-pave stones. Ideal for engagements or grand celebrations.",
        "price": 4500.00,
        "stock": 5,
        "image_url": "https://images.unsplash.com/photo-1605100804763-247f67b3557e?q=80&w=1000&auto=format&fit=crop"
    },
    {
        "name": "Etheria Pearl Necklace",
        "description": "Lustrous South Sea pearls hand-strung on a delicate 22k yellow gold chain. A timeless piece that radiates sophistication.",
        "price": 1200.50,
        "stock": 10,
        "image_url": "https://images.unsplash.com/photo-1599643477877-530eb83abc8e?q=80&w=1000&auto=format&fit=crop"
    },
    {
        "name": "Midnight Velvet Sapphire Earrings",
        "description": "Deep blue sapphires encased in a vintage-inspired platinum setting. These earrings capture the essence of high-society elegance.",
        "price": 2850.00,
        "stock": 3,
        "image_url": "https://images.unsplash.com/photo-1635767798638-3e25d30925a9?q=80&w=1000&auto=format&fit=crop"
    },
    {
        "name": "Gilded Serpent Bracelet",
        "description": "An intricately designed 24k gold bracelet with a modern snake-like wrap design. Features tiny emerald accents for eyes.",
        "price": 3200.00,
        "stock": 7,
        "image_url": "https://images.unsplash.com/photo-1611591437281-460bfbe1220a?q=80&w=1000&auto=format&fit=crop"
    },
    {
        "name": "Rose Gold Twilight Pendant",
        "description": "A heart-shaped rose gold pendant with a subtle pink tourmaline centerpiece. Perfect for a romantic gift.",
        "price": 850.00,
        "stock": 15,
        "image_url": "https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?q=80&w=1000&auto=format&fit=crop"
    }
]

def seed_database():
    try:
        print("Connecting to database...")
        with engine.connect() as connection:
            print("Successfully connected. Clearing existing products (optional)...")
            # Clear table first to avoid duplication during testing (optional)
            # connection.execute(text("DELETE FROM products"))
            
            print(f"Seeding {len(products)} products...")
            
            insert_query = text("""
                INSERT INTO products (name, description, price, stock, category, image_url)
                VALUES (:name, :description, :price, :stock, :category, :image_url)
            """)
            
            # Clear existing products to refresh with categories
            connection.execute(text("DELETE FROM products"))
            
            for product in products:
                # Add a dummy category based on name
                if 'Ring' in product['name']: product['category'] = 'Rings'
                elif 'Necklace' in product['name']: product['category'] = 'Necklaces'
                elif 'Earrings' in product['name']: product['category'] = 'Earrings'
                elif 'Bracelet' in product['name']: product['category'] = 'Bracelets'
                else: product['category'] = 'Pendants'
                
                connection.execute(insert_query, product)

            
            connection.commit()
            print("Database successfully seeded!")
            
    except Exception as e:
        print(f"Error seeding database: {e}")

if __name__ == "__main__":
    seed_database()
