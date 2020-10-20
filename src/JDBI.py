import subprocess
import json
import urllib.parse


class JDBI:

    def __init__(self, mem_cache_ip, mem_cache_port, timeout=4):
        self.working_directory = __file__.replace('JDBI.py', '') + 'PyToPHP.php'
        self.mem_cache_ip = mem_cache_ip
        self.mem_cache_port = mem_cache_port
        self.timeout = timeout
        self.connected = False
        self.db_name = ''
        self.db_password = ''

    def _execute_php_command(self, variables):
        proc = subprocess.Popen('php ' + self.working_directory + ' ' + urllib.parse.quote(json.dumps(variables)),
                                shell=True, stdout=subprocess.PIPE)
        return proc.stdout.read()

    def query(self, query):
        request = {
            'ACTION': 'EXECUTE_QUERY',
            'SOCK_CONNECTION': {
                'IP': self.mem_cache_ip,
                'PORT': self.mem_cache_port,
                'TIMEOUT': self.timeout
            },
            'DATA': {
                'QUERY': query
            }
        }
        if self.connected:
            request['DATA']['DB_NAME'] = self.db_name
            request['DATA']['DB_PASSWORD'] = self.db_password

        result = json.loads(self._execute_php_command(request))
        if result['TYPE_RESULT'] == 'ERROR':
            raise Exception(result['RESULT'])
        else:
            result = result['RESULT']
        return result

    def connect(self, db_name, db_password):
        request = {
            'ACTION': 'CONNECT',
            'SOCK_CONNECTION': {
                'IP': self.mem_cache_ip,
                'PORT': self.mem_cache_port,
                'TIMEOUT': self.timeout
            },
            'DATA': {
                'DB_NAME': db_name,
                'DB_PASSWORD': db_password
            }
        }
        result = json.loads(self._execute_php_command(request))
        if result['TYPE_RESULT'] == 'ERROR':
            raise Exception(result['RESULT'])
        else:
            result = bool(result['RESULT'])

        if result:
            self.connected = True
            self.db_name = db_name
            self.db_password = db_password

        return result
