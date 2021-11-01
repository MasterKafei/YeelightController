# Yeelight controller

## Introduction

Yeelight controller is a Symfony 5.3 project developed in php 8.0 which allows you to control your Yeelight lights from the command line. Several features have been implemented such as the state of the light (on, off), the color in hexadecimal, the temperature, etc. There is also a scenario system that allows you to record a series of instructions to apply to a list of Yeelights. These scenarios are programmable in yaml and can be executed from the command line.

## Installation

Like any Symfony project, it is necessary to have php & composer to install Yeelight Controller. No other service is needed. (No database, web server etc).

To install the project, just run the command `composer install` at the root of the project.

## Configuration

All the configuration takes place in the `config/settings.yaml` file.

### Yeelights lights

The configuration takes place in the `config/settings.yaml` file under the `yeelights` keyword.
By default there is already a list of Yeelight lights which will surely be wrong for your use. However, this list gives you an overview of the configuration to use.

```
settings:
    yeelights:
        ...
```

This keyword's value is the list of the lights you want to control.
A light is configured as follows:

```
    kitchen:
        name: Cuisine
        ip: 192.168.1.23
        port: 80 # Optional: by default port 55443 is used
```

`kitchen` is not used in the app, I gave it a name to avoid duplicates in my configuration. You can use a list without keys or modify it to whatever you like.

`name` is the name of the yeelight you want to control with the solution. This value will not change the configuration you may have done in the Yeelight app in any way. This value will however be necessary to identify the light in the scenario configuration.

`ip` is the ip address of your light. You can find this information in the `Yeelight app > you light > Settings > Device info > Ip address`

`port` is the port of your light. You don't have to set this value. By default, the port will be 55443 (the port used by default for Yeelight lights). If you don't know this value, I advise you to not set it.

### Scenarios

The scenarios are also configured in the `config/settings.yaml` file under the` scenarios` key.
By default, there is already a scenario list which will probably be wrong for your use (different light names), however if you update the light names define, the configuration will be usable. However, this list gives you an overview of the configuration to use.

```
    settings:
        yeelights: []
        scenarios:
            ...
```

This keyword ask for a list containing the scenarios you want to apply to your lights.
A scenario is configured as follows:
```
    all_white:
        name: AllWhite
        instructions:
            -
                lights: all
                power: on
                color: 0xFFFFFF
                hue: 359
                brigth: 100
                temperature:
                    - 6500
```

`all_white` is not used in the app, like for lights, I gave it a name to avoid duplicates in my configuration. You can use a list without keys or modify it to whatever you like.

`name` corresponds to the name of the scenario. This information will be used in the command `php bin/console app:start <name>`

`instructions` is a list of instructions which will be executed in the order of definition. An instruction is defined as follows:

`ligths` (`string` | `array`) is the only required key. Indicates which light the instruction will be applied. You can use the names of the lights you configured as a value or the constant `all` to apply the instruction to all configured lights.

`power` (`string`) indicates the state of the light (on, off). Possible values are `on`, `off`, and `toggle`.

`color` (`int`) indicates the color in hex that will be applied to the light. You can enter a hex value. Possible values: 1 - 16777215 (0x000001 - 0xFFFFFF).

`hue` (`int`) indicates the hue of the light from 0 to 359.

`bright` (`int`) indicates the percentage of brightness of the light from 1 to 100.

`temperature` (`int` | `array`) indicates the color temperature. Two types of value possible. Either you indicate a number it will correspond to the color temperature. Consider an array of three values which will allow you to add the effect (`smooth` |` sudden`) and the transition duration (minimum to 30). To summarize the table, the first value corresponds to the temperature, the second the effect, and the third the duration.

`continueAfter` (`int`) indicates the time to wait before going to the next instruction. Value cannot be less than 0.

## How to use it
To use a scenario, you can use the command `php bin/console app:start [scenario_name]`. The `scenario_name` parameter is optional, if you do not set it, you will have to choose from all the configured scenarios