using System;
using System.Diagnostics;
using System.IO;
using System.Management;
using System.Runtime.InteropServices;

namespace MrDetective
{
    public class SystemInfo
    {
        public OSInfo GetOSInfo()
        {
            return new OSInfo
            {
                Name = RuntimeInformation.OSDescription,
                Architecture = RuntimeInformation.OSArchitecture.ToString(),
                Version = Environment.OSVersion.VersionString,
                Framework = RuntimeInformation.FrameworkDescription
            };
        }

        public CPUInfo GetCPUInfo()
        {
            using (var searcher = new ManagementObjectSearcher("SELECT * FROM Win32_Processor"))
            {
                foreach (ManagementObject obj in searcher.Get())
                {
                    return new CPUInfo
                    {
                        Name = obj["Name"].ToString(),
                        Cores = int.Parse(obj["NumberOfCores"].ToString()),
                        Threads = int.Parse(obj["NumberOfLogicalProcessors"].ToString()),
                        ClockSpeed = $"{obj["MaxClockSpeed"]} MHz"
                    };
                }
            }
            return new CPUInfo();
        }

        public MemoryInfo GetMemoryInfo()
        {
            using (var searcher = new ManagementObjectSearcher("SELECT * FROM Win32_PhysicalMemory"))
            {
                ulong totalMemory = 0;
                string memoryType = "Unknown";
                
                foreach (ManagementObject obj in searcher.Get())
                {
                    totalMemory += ulong.Parse(obj["Capacity"].ToString());
                    memoryType = GetMemoryType(obj["MemoryType"].ToString());
                }

                return new MemoryInfo
                {
                    TotalGB = Math.Round(totalMemory / (1024.0 * 1024 * 1024), 2),
                    Type = memoryType
                };
            }
        }

        private string GetMemoryType(string memoryType)
        {
            return memoryType switch
            {
                "20" => "DDR",
                "21" => "DDR2",
                "24" => "DDR3",
                "26" => "DDR4",
                "30" => "DDR5",
                _ => "Unknown"
            };
        }
    }

    public class OSInfo
    {
        public string Name { get; set; }
        public string Version { get; set; }
        public string Architecture { get; set; }
        public string Framework { get; set; }
    }

    public class CPUInfo
    {
        public string Name { get; set; }
        public int Cores { get; set; }
        public int Threads { get; set; }
        public string ClockSpeed { get; set; }
    }

    public class MemoryInfo
    {
        public double TotalGB { get; set; }
        public string Type { get; set; }
    }
}