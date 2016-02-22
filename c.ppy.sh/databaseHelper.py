import pymysql
import bcolors
import sys

class db:
	connection = None


	def __init__(self, __host, __username, __password, __database):
		"""Open a db connection

		__host -- MySQL host name
		__username -- MySQL username
		__password -- MySQL password
		__database -- MySQL database name"""

		self.connection = pymysql.connect(host=__host, user=__username, password=__password, db=__database, cursorclass=pymysql.cursors.DictCursor)


	def bindParams(self, __query, __params):
		"""Replace every ? with the respective escaped parameter in array

		__query -- query with ?s
		__params -- array with params

		return -- new query"""

		for i in __params:
			escaped = self.connection.escape(i)
			__query = __query.replace("?", str(escaped), 1)

		return __query


	def execute(self, __query, __params = None):
		"""Execute a SQL query

		__query -- query, can contain ?s
		__params -- array with params. Optional"""

		#try:
		with self.connection.cursor() as cursor:
			# Bind params if needed
			if (__params != None):
				__query = self.bindParams(__query, __params)

			# Execute the query
			cursor.execute(__query)

			# Commit changes
			self.connection.commit()
		#except:
			#print(bcolors.RED+"[!] Error while executing query ("+str(__query)+")"+"\r\n"+str(sys.exc_info()[1])+bcolors.ENDC)
		#finally:
			self.connection.close()


	def fetch(self, __query, __params = None, __all = False):
		"""Fetch the first (or all) element(s) of SQL query result

		__query -- query, can contain ?s
		__params -- array with params. Optional
		__all -- if true, will fetch all values. Same as fetchAll

		return -- dictionary with result data or False if failed"""

		#try:
		with self.connection.cursor() as cursor:
			# Bind params if needed
			if (__params != None):
				__query = self.bindParams(__query, __params)

			# Execute the query with binded params
			cursor.execute(self.bindParams(__query, __params))

			# Get first result and return it
			if (__all == False):
				return cursor.fetchone()
			else:
				return cursor.fetchall()
		#except:
			#print(bcolors.RED+"[!] Error while fetching values ("+str(__query)+")"+"\r\n"+str(sys.exc_info()[1])+bcolors.ENDC)
		#finally:
			self.connection.close()


	def fetchAll(self, __query, __params = []):
		"""Fetch the all elements of SQL query result

		__query -- query, can contain ?s
		__params -- array with params. Optional

		return -- dictionary with result data"""

		return self.fetch(__query, __params, True)
