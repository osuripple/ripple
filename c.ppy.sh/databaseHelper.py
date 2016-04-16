import pymysql
import bcolors
import consoleHelper
import threading

class db:
	"""A MySQL database connection"""

	connection = None
	disconnected = False
	pingTime = 600

	def __init__(self, __host, __username, __password, __database, __pingTime = 600):
		"""
		Connect to MySQL database

		__host -- MySQL host name
		__username -- MySQL username
		__password -- MySQL password
		__database -- MySQL database name
		__pingTime -- MySQL database ping time (default: 600)
		"""

		self.connection = pymysql.connect(host=__host, user=__username, password=__password, db=__database, cursorclass=pymysql.cursors.DictCursor, autocommit=True)
		self.pingTime = __pingTime
		self.pingLoop()


	def bindParams(self, __query, __params):
		"""
		Replace every ? with the respective **escaped** parameter in array

		__query -- query with ?s
		__params -- array with params

		return -- new query
		"""

		for i in __params:
			escaped = self.connection.escape(i)
			__query = __query.replace("?", str(escaped), 1)

		return __query


	def execute(self, __query, __params = None):
		"""
		Execute a SQL query

		__query -- query, can contain ?s
		__params -- array with params. Optional
		"""


		with self.connection.cursor() as cursor:
			try:
				# Bind params if needed
				if __params != None:
					__query = self.bindParams(__query, __params)

				# Execute the query
				cursor.execute(__query)
			finally:
				# Close this connection
				cursor.close()


	def fetch(self, __query, __params = None, __all = False):
		"""
		Fetch the first (or all) element(s) of SQL query result

		__query -- query, can contain ?s
		__params -- array with params. Optional
		__all -- if true, will fetch all values. Same as fetchAll

		return -- dictionary with result data or False if failed
		"""


		with self.connection.cursor() as cursor:
			try:
				# Bind params if needed
				if __params != None:
					__query = self.bindParams(__query, __params)

				# Execute the query with binded params
				cursor.execute(__query)

				# Get first result and return it
				if __all == False:
					return cursor.fetchone()
				else:
					return cursor.fetchall()
			finally:
				# Close this connection
				cursor.close()


	def fetchAll(self, __query, __params = None):
		"""
		Fetch the all elements of SQL query result

		__query -- query, can contain ?s
		__params -- array with params. Optional

		return -- dictionary with result data
		"""

		return self.fetch(__query, __params, True)

	def pingLoop(self):
		"""
		Pings MySQL server. We need to ping/execute a query at least once every 8 hours
		or the connection will die.
		If called once, will recall after 30 minutes and so on, forever
		CALL THIS FUNCTION ONLY ONCE!
		"""

		# Default loop time
		time = self.pingTime

		# Make sure the connection is alive
		try:
			# Try to ping and reconnect if not connected
			self.connection.ping()
			if self.disconnected == True:
				# If we were disconnected, set disconnected to false and print message
				self.disconnected = False
				consoleHelper.printColored("> Reconnected to MySQL server!", bcolors.GREEN)
		except:
			# Can't ping MySQL server. Show error and call loop in 5 seconds
			consoleHelper.printColored("[!] CRITICAL!! MySQL connection died! Make sure your MySQL server is running! Checking again in 5 seconds...", bcolors.RED)
			self.disconnected = True
			time = 5

		# Schedule a new check (endless loop)
		threading.Timer(time, self.pingLoop).start()
