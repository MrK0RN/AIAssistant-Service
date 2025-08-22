import socket
import socks
import requests
from urllib.parse import urlparse
from typing import Optional, Tuple


class GlobalSocksProxy:
    def __init__(self, proxy_url: str = None):
        """
        Инициализация прокси-менеджера

        :param proxy_url: URL прокси в формате socks5://[user:pass@]host:port
        """
        self.original_socket = socket.socket
        self.proxy_url = proxy_url
        self.original_ip = self._get_current_ip()
        self.active = False

    def _parse_proxy_url(self, proxy_url: str) -> Tuple[str, str, int, Optional[str], Optional[str]]:
        """Парсинг URL прокси на составляющие"""
        parsed = urlparse(proxy_url)
        return (
            parsed.scheme,  # proto
            parsed.hostname,  # host
            parsed.port,  # port
            parsed.username,  # username
            parsed.password  # password
        )

    def _get_current_ip(self) -> Optional[str]:
        """Получение текущего IP (без прокси)"""
        try:
            response = requests.get('https://api.ipify.org?format=json', timeout=5)
            return response.json().get('ip')
        except:
            return None

    def activate_proxy(self, proxy_url: str = None) -> bool:
        """
        Активация глобального прокси

        :param proxy_url: URL прокси (если None, использует установленный при инициализации)
        :return: True если прокси активирован успешно
        """
        if proxy_url:
            self.proxy_url = proxy_url

        if not self.proxy_url:
            raise ValueError("Proxy URL not specified")

        try:
            proto, host, port, username, password = self._parse_proxy_url(self.proxy_url)

            if proto not in ['socks4', 'socks5']:
                raise ValueError(f"Unsupported proxy protocol: {proto}")

            proxy_type = socks.SOCKS5 if proto == 'socks5' else socks.SOCKS4

            # Устанавливаем глобальный прокси
            socks.set_default_proxy(
                proxy_type=proxy_type,
                addr=host,
                port=port,
                username=username,
                password=password
            )
            socket.socket = socks.socksocket
            self.active = True
            return True
        except Exception as e:
            print(f"Proxy activation failed: {str(e)}")
            self.deactivate_proxy()
            return False

    def deactivate_proxy(self) -> None:
        """Деактивация прокси и восстановление оригинального сокета"""
        socket.socket = self.original_socket
        self.active = False

    def test_proxy(self) -> Tuple[bool, Optional[str]]:
        """
        Проверка работоспособности прокси

        :return: (success: bool, ip_or_error: str)
        """
        if not self.active:
            if not self.activate_proxy():
                return (False, "Proxy not activated")

        try:
            # Проверяем IP через прокси
            proxy_ip = self._get_current_ip()

            if not proxy_ip:
                return (False, "Failed to get IP through proxy")

            # Сравниваем с оригинальным IP
            if proxy_ip == self.original_ip:
                return (False, f"Proxy not working (IP matches original: {proxy_ip})")

            return (True, proxy_ip)

        except Exception as e:
            return (False, f"Proxy test failed: {str(e)}")

    def __enter__(self):
        """Поддержка контекстного менеджера"""
        self.activate_proxy()
        return self

    def __exit__(self, exc_type, exc_val, exc_tb):
        """Поддержка контекстного менеджера"""
        self.deactivate_proxy()