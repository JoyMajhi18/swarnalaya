import os
import bcrypt
import pymysql
from dotenv import load_dotenv

# Load environment variables
load_dotenv()

DB_HOST = os.getenv("DB_HOST", "127.0.0.1")
DB_PORT = int(os.getenv("DB_PORT", 3306))
DB_USER = os.getenv("DB_USER", "root")
DB_PASS = os.getenv("DB_PASS", "")
DB_NAME = os.getenv("DB_NAME", "joy")

def seed_admin():
    print("Seeding admin user...")
    
    # User details
    name = "Admin Joy"
    email = "joyaddmin@gmail.com"
    password = "admin123"
    role = "admin"

    # Hash password using bcrypt
    # bcrypt in python generates $2b$ hashes, which are recognized by PHP's password_verify.
    hashed_password = bcrypt.hashpw(password.encode('utf-8'), bcrypt.gensalt())
    
    try:
        conn = pymysql.connect(
            host=DB_HOST,
            port=DB_PORT,
            user=DB_USER,
            password=DB_PASS,
            database=DB_NAME
        )
        with conn.cursor() as cursor:
            # Check if user already exists
            cursor.execute("SELECT id FROM users WHERE email = %s", (email,))
            if cursor.fetchone():
                print(f"User with email '{email}' already exists.")
                return

            # Insert new admin user
            sql = """
                INSERT INTO users (name, email, password, role)
                VALUES (%s, %s, %s, %s)
            """
            cursor.execute(sql, (name, email, hashed_password.decode('utf-8'), role))
            conn.commit()
            print(f"Successfully seeded admin user: {email}")

    except Exception as e:
        print(f"Error seeding admin user: {e}")
    finally:
        if 'conn' in locals() and conn:
            conn.close()

if __name__ == "__main__":
    seed_admin()
