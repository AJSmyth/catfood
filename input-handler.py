#!/usr/bin/env python3
import RPi.GPIO as GPIO
from time import sleep
import mysql.connector
from datetime import datetime
import pytz

GPIO.setmode(GPIO.BCM)
tz = pytz.timezone('America/Los_Angeles')

#from https://www.freecodecamp.org/news/connect-python-with-sql/
def sql_connect(host_name, user_name, user_password):
    connection = None
    try:
        connection = mysql.connector.connect(
		host=host_name,
		user=user_name,
		passwd=user_password,
		autocommit=True)
    except Error as err:
        print("Error connecting: " + err)

    return connection

#from https://www.freecodecamp.org/news/connect-python-with-sql/
def execute_query(connection, query):
    cursor = connection.cursor()
    try:
        cursor.execute(query)
    except Error as err:
        print(f"Error: '{err}'")

#from https://www.freecodecamp.org/news/connect-python-with-sql/
def read_query(connection, query):
    cursor = connection.cursor()
    result = None
    try:
        cursor.execute(query)
        result = cursor.fetchall()
        return result
    except Error as err:
        print(f"Error reading query: '{err}'")


green = [26, 17]
red = [19, 4]
buttons = [5, 22]

GPIO.setup(green, GPIO.OUT, initial=GPIO.LOW)
GPIO.setup(red, GPIO.OUT, initial=GPIO.HIGH)
GPIO.setup(buttons, GPIO.IN, pull_up_down=GPIO.PUD_UP)

def pin_to_state(pin, res):
	 return [res[0] ^ (pin == buttons[0]), res[1] ^ (pin == buttons[1])]

def toggle_leds(res):
	GPIO.output(green[0], res[0])
	GPIO.output(red[0], not res[0])
	GPIO.output(green[1], res[1])
	GPIO.output(red[1], not res[1])

print("Finished setup")
q1 = "SELECT morning, night FROM catfood.fed WHERE date='"
conn = sql_connect('localhost', 'user', 'pass')

def input_callback(pin):
	#don't know much about threading, so made new variables justincase
	connT = sql_connect('localhost', 'user', 'pass')
	nowT = datetime.now(tz).strftime("%Y-%m-%d")
	resT = read_query(connT, q1 + nowT + "';")
	if res:
		new = pin_to_state(pin, resT[0])
		execute_query(connT, "UPDATE catfood.fed SET morning=" + str(new[0]) + ", night=" + str(new[1]) + " WHERE date='" + nowT + "';")

def input_callback_test(pin):
	print("Callback on pin " + str(pin))

GPIO.add_event_detect(buttons[0], GPIO.FALLING, callback=input_callback)
GPIO.add_event_detect(buttons[1], GPIO.FALLING, callback=input_callback)

while True:
	if conn == None:
		print("CONN NONETYPE")
		conn = sql_connect('localhost', 'pass', 'user')

	now = datetime.now(tz).strftime("%Y-%m-%d")
	res = read_query(conn, q1 + now + "';")
	toggle_leds(res[0] if res else [0,0])

	if not res:
		execute_query(conn, "INSERT INTO catfood.fed VALUES (0, 0, '" + now + "');")
	sleep(0.5)
