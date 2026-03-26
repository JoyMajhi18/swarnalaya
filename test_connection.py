import os
from sqlalchemy import text
from database import engine
from dotenv import load_dotenv

load_dotenv()

def test_connection():
    try:
        connection_url = os.getenv("DATABASE_URL")
        print(f"Testing connection to: {connection_url}")
        
        with engine.connect() as connection:
            result = connection.execute(text("SELECT VERSION()"))
            version = result.fetchone()
            print(f"Success! Database version: {version[0]}")
            
    except Exception as e:
        print(f"Connection failed: {e}")

if __name__ == "__main__":
    test_connection()
