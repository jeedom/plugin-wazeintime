Waze in Time 
============

Descripción 
-----------

Este complemento le permite tener la información del viaje (tráfico tomado en cuenta) a través de
Waze Es posible que este complemento ya no funcione si Waze ya no acepta eso
consulta su sitio

![wazeintime screenshot1](../images/wazeintime_screenshot1.jpg)

Configuración 
-------------

### Configuración del plugin: 

a. Instalación / Creación

Para usar el complemento, debe descargar, instalar y
activarlo como cualquier complemento de Jeedom.

Después de eso, tendrá que crear su (s) viaje (s) :

Vaya al menú de complementos / organización, encontrará el
Complemento de duración de Waze :

![configuration1](../images/configuration1.jpg)

Luego, llegará a la página que enumerará su equipo (usted
puede tener múltiples rutas) y que le permitirán crear

![wazeintime screenshot2](../images/wazeintime_screenshot2.jpg)

Haga clic en el botón Agregar viaje o en el botón + :

![config2](../images/config2.jpg)

Luego llegará a la página de configuración de su viaje:

![wazeintime screenshot3](../images/wazeintime_screenshot3.jpg)

En esta página encontrarás tres secciones. :

yo. General

En esta sección encontrará todas las configuraciones de jeedom. Un
conoce el nombre de tu equipo, el objeto que deseas
asócielo, categoría, si desea que el equipo esté activo o
no, y finalmente si quieres que sea visible en el tablero.

yo. Configuracion

Esta sección es una de las más importantes y le permite ajustar el
punto inicial y final :

-   Estas informaciones deben ser las latitudes y longitudes de las posiciones.

-   Se pueden encontrar utilizando el sitio provisto en
    haciendo clic en el enlace de la página (solo tiene que ingresar un
    dirección y haga clic en obtener coordenadas GPS)

    yo. Panel de control

![config3](../images/config3.jpg)

-   Duración 1 : duración del viaje 1

-   Duración 2 : tiempo de viaje con la ruta alternativa

-   Trayecto 1 : Trayecto 1

-   Trayecto 2 : Ruta alternativa

-   Duración trayecto de vuelta 1 : tiempo de regreso con viaje 1

-   Duración trayecto de vuelta 2 : hora de regreso con la ruta alternativa

-   Trayecto de vuelta 1 : Trayecto de vuelta 1

-   Trayecto de vuelta 2 : Viaje de regreso alternativo

-   Refrescar : Actualizar información

Todos estos comandos están disponibles a través de escenarios y a través del tablero

### El widget : 

![wazeintime screenshot1](../images/wazeintime_screenshot1.jpg)

-   El botón en la parte superior derecha actualiza la información.

-   Toda la información es visible (para viajes, si el viaje es
    largo, se puede truncar pero la versión completa es visible en
    dejando el mouse sobre)

### ¿Cómo se actualizan las noticias? : 

La información se actualiza una vez cada 30 minutos.. Usted puede
actualícelos a pedido mediante un escenario con el comando Actualizar, o
a través del tablero con las flechas dobles

Cambios 
=========

Registro de cambios detallado :
<https://github.com/jeedom/plugin-wazeintime/commits/stable>
