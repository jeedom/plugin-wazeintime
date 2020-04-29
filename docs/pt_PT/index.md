Waze in Time 
============

Descrição 
-----------

Este plugin permite que você tenha as informações da viagem (tráfego levado em consideração) via
Waze. Esse plug-in pode não funcionar mais se o Waze não aceitar mais
consulta seu site

![wazeintime screenshot1](../images/wazeintime_screenshot1.jpg)

Configuração 
-------------

### Configuração do plugin: 

a. Instalação / Criação

Para usar o plug-in, você precisa baixar, instalar e
ativá-lo como qualquer plugin Jeedom.

Depois disso, você terá que criar sua (s) viagem (s) :

Vá para o menu plugins / organização, você encontrará o
Plug-in Duração do Waze :

![configuration1](../images/configuration1.jpg)

Você chegará à página que listará seu equipamento (você
pode ter várias rotas) e que permitirá criar

![wazeintime screenshot2](../images/wazeintime_screenshot2.jpg)

Clique no botão Adicionar viagem ou no botão + :

![config2](../images/config2.jpg)

Você chegará à página de configuração da sua viagem:

![wazeintime screenshot3](../images/wazeintime_screenshot3.jpg)

Nesta página você encontrará três seções :

eu Geral

Nesta seção, você encontrará todas as configurações de jeedom. Um
saber o nome do seu equipamento, o objeto que você deseja
associe-o, categoria, se desejar que o equipamento esteja ativo ou
não e, finalmente, se você quiser que fique visível no painel.

eu Configuração

Esta seção é uma das mais importantes, pois permite ajustar o
ponto inicial e final :

-   Essas informações devem ser as latitudes e longitudes das posições

-   Eles podem ser encontrados usando o site fornecido em
    clicando no link da página (basta inserir um
    endereço e clique em obter coordenadas GPS)

    eu Painel de controle

![config3](../images/config3.jpg)

-   1 duração : duração da viagem 1

-   Duração dois : tempo de viagem com a rota alternativa

-   Caminho 1 : Caminho 1

-   Rota 2 : Rota alternativa

-   Duração 1 retorno : tempo de retorno com viagem 1

-   Duração 2 back : hora de retorno com a rota alternativa

-   Viagem de regresso 1 : Viagem de regresso 1

-   Viagem de regresso 2 : Viagem de retorno alternativa

-   Legal : Atualizar informações

Todos esses comandos estão disponíveis através de cenários e através do painel

### O widget : 

![wazeintime screenshot1](../images/wazeintime_screenshot1.jpg)

-   O botão no canto superior direito atualiza as informações.

-   Toda a informação é visível (para viagens, se a viagem for
    por muito tempo, pode ser truncado, mas a versão completa é visível em
    deixando o mouse sobre)

### Como as notícias são atualizadas : 

As informações são atualizadas uma vez a cada 30 minutos. Você pode
atualize-os sob demanda via cenário com o comando refresh ou
através do traço com as setas duplas

Changelog 
=========

Registro de alterações detalhado :
<https://github.com/jeedom/plugin-wazeintime/commits/stable>
