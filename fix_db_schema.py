from database import engine
from sqlalchemy import text

def fix_database():
    try:
        with engine.connect() as conn:
            print("Adding 'payments' table...")
            # Create payments table
            conn.execute(text("""
                CREATE TABLE IF NOT EXISTS payments (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    order_id INT NOT NULL,
                    amount DECIMAL(10, 2) NOT NULL,
                    payment_method VARCHAR(50) NOT NULL,
                    payment_status ENUM('Pending', 'Paid', 'Failed') DEFAULT 'Pending',
                    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
                )
            """))
            
            print("Renaming 'delivery_address' to 'address' in 'orders' table...")
            # Rename column in orders if it exists as delivery_address
            # Note: MySQL 5.7 uses CHANGE, MySQL 8.0 uses RENAME COLUMN
            # To be safe, we'll try to add 'address' and copy data if 'delivery_address' exists
            try:
                conn.execute(text("ALTER TABLE orders CHANGE COLUMN delivery_address address TEXT NOT NULL"))
            except Exception as e:
                print(f"Column rename info: {e}")
                # Maybe already renamed or doesn't exist
            
            conn.commit()
            print("Database fix completed successfully!")
    except Exception as e:
        print(f"Error fixing database: {e}")

if __name__ == "__main__":
    fix_database()
