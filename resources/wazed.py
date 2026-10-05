from __future__ import annotations

from jeedomdaemon import BaseDaemon
from pywaze import route_calculator


class WazeDaemon(BaseDaemon):
    def __init__(self) -> None:
        super().__init__(on_message_cb=self._on_message)

    async def _on_message(self, message: dict):
        if 'id' not in message or 'start' not in message or 'end' not in message:
            return

        start = message['start']
        end = message['end']

        options = {
            'region': message.get('region', 'EU'),
            'vehicle_type': message.get('vehicle_type', None),
            'avoid_toll_roads': message.get('avoid_toll_roads', False),
            'avoid_subscription_roads': message.get('avoid_subscription_roads', False),
            'avoid_ferries': message.get('avoid_ferries', False)
        }

        await self.send_to_jeedom({message['id']: await self.__get_routes(start, end, options)})
        await self.send_to_jeedom({message['id']: await self.__get_routes(end, start, options, 'ret')})

    async def __get_routes(self, start, end, options: dict, prefix=''):

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
                self._logger.debug("Received %i results", len(results))
                for i, route in enumerate(results):
                    self._logger.info(f"Route {i+1} name: {route.name}, duration: {route.duration}, distance: {route.distance} ")
                    routes.append({
                        f'route{prefix}name{i+1}': route.name,
                        f'time{prefix}{i+1}': round(route.duration),
                        f'distance{prefix}{i+1}': route.distance
                    })

        return routes


WazeDaemon().run()
