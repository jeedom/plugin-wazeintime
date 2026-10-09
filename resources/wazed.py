from __future__ import annotations

from typing import Literal

from jeedomdaemon import BaseDaemon
from pywaze import route_calculator

VehicleType = Literal[None, "TAXI", "MOTORCYCLE"]


class WazeDaemon(BaseDaemon):
    def __init__(self) -> None:
        super().__init__(on_message_cb=self._on_message)

    async def _on_message(self, message: dict):
        if 'id' not in message or 'start' not in message or 'end' not in message:
            return

        start = message['start']
        end = message['end']

        options = self.__build_options(message)

        self._logger.info(f"Calculating outbound and return routes from {start} to {end} with options: {options}")
        await self.send_to_jeedom({message['id']: await self.__get_routes(start, end, options)})
        await self.send_to_jeedom({message['id']: await self.__get_routes(end, start, options, 'return_')})

    async def __get_routes(self, start: str, end: str, options: dict, prefix: str = ''):

        routes = []

        async with route_calculator.WazeRouteCalculator(region=options['region']) as client:
            try:
                results = await client.calc_routes(
                    start,
                    end,
                    vehicle_type=options['vehicle_type'],
                    avoid_toll_roads=options['avoid_toll_roads'],
                    avoid_subscription_roads=options['avoid_subscription_roads'],
                    avoid_ferries=options['avoid_ferries'],
                    alternatives=3
                )
            except Exception as e:
                self._logger.error(f"Error calculating routes: {e}")
            else:
                self._logger.info("Received %i results", len(results))
                for i, route in enumerate(results):
                    self._logger.debug(f"Route {i+1} name: {route.name}, duration: {route.duration}, distance: {route.distance} ")
                    routes.append({
                        f'{prefix}name{i+1}': route.name,
                        f'{prefix}duration{i+1}': round(route.duration),
                        f'{prefix}distance{i+1}': route.distance
                    })

        return routes

    def __build_options(self, message: dict) -> dict:

        # accepted values for region: 'EU', 'NA', 'IL', 'AU'; not necessary in our case

        return {
            'region': message.get('region', 'EU'),
            'vehicle_type': self.__to_vehicle_type(message.get('vehicle_type', None)),
            'avoid_toll_roads': self.__to_bool(message.get('avoid_toll_roads', False)),
            'avoid_subscription_roads': self.__to_bool(message.get('avoid_subscription_roads', False)),
            'avoid_ferries': self.__to_bool(message.get('avoid_ferries', False))
        }

    def __to_vehicle_type(self, value: str | None = None) -> VehicleType:
        if value is None:
            return None

        value = value.upper()
        if value == 'TAXI':
            return 'TAXI'
        if value == 'MOTORCYCLE':
            return 'MOTORCYCLE'
        return None

    def __to_bool(self, value) -> bool:
        if isinstance(value, bool):
            return value
        if isinstance(value, str):
            return value.lower() in ('true', '1')
        if isinstance(value, int):
            return value != 0
        return False


WazeDaemon().run()
