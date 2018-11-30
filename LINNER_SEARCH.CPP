/*Write a program to accept N numbers from the user and store them in an array. Then, accept
another number from the user and search that using Linear Search.*/
#include<iostream>
using namespace std;
void linear_search(int arr[],int size,int element)
{
	int flag=0;
	for(int i=0;i<size;i++)
	{
		if(arr[i]==element)
		{
		   cout<<"Element "<<element<<" is at "<<i+1<<" position"<<endl;
		   flag=1;
		   break;
		}
	}
	if(flag==0)
	  cout<<"ERROR! Element not found";
}
int main()
{
	int size,element;
	cout<<"Enter the size of array :-> ";
	cin>>size;
	int *arr=new int[size];
	cout<<"Enter elements ->"<<endl;
	for(int i=0;i<size;i++)
	   cin>>arr[i];
	cout<<"Enter the element to be searched : ";
	cin>>element;
	linear_search(arr,size,element);
	return 0;
}
